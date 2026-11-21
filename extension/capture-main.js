(() => {
  if (window.__maiusBugRecorderInstalled) return;
  window.__maiusBugRecorderInstalled = true;

  const maxText = 4096;
  const sensitive = /authorization|cookie|password|passwd|secret|token|api[-_]?key|session/i;
  const send = (kind, entry) => window.postMessage({ source: "maius-bug-recorder", kind, entry }, "*");

  function clean(value, depth = 0) {
    if (depth > 4) return "[depth limit]";
    if (Array.isArray(value)) return value.slice(0, 50).map((item) => clean(item, depth + 1));
    if (value && typeof value === "object") {
      return Object.fromEntries(Object.entries(value).slice(0, 100).map(([key, item]) => [
        key,
        sensitive.test(key) ? "[REDACTED]" : clean(item, depth + 1),
      ]));
    }
    if (typeof value === "string") return value.slice(0, maxText);
    return value;
  }

  function textBody(value) {
    if (typeof value !== "string") return null;
    try { return JSON.stringify(clean(JSON.parse(value))); } catch {}
    return value.replace(/((?:authorization|set-cookie|cookie|password|passwd|secret|token|api[-_]?key)\s*[=:]\s*)(?:bearer\s+)?[^&\s,;}]+/ig, "$1[REDACTED]").slice(0, maxText);
  }

  function cleanUrl(value) {
    try {
      const url = new URL(value, location.href);
      for (const key of [...url.searchParams.keys()]) {
        if (sensitive.test(key)) url.searchParams.set(key, "[REDACTED]");
      }
      return url.href;
    } catch { return String(value).slice(0, maxText); }
  }

  function headersFrom(value) {
    const result = [];
    try {
      new Headers(value).forEach((item, name) => {
        if (!sensitive.test(name)) result.push({
          name,
          value: /^(?:referer|location)$/i.test(name) ? cleanUrl(item) : (textBody(item) || "").slice(0, 1000),
        });
      });
    } catch {}
    return result;
  }

  async function responseText(response) {
    const type = response.headers.get("content-type") || "";
    if (!/json|text|xml|javascript|form-urlencoded/i.test(type) || !response.body) return null;
    const reader = response.body.getReader();
    const chunks = [];
    let size = 0;
    try {
      while (size < maxText) {
        const { done, value } = await reader.read();
        if (done) break;
        const part = value.slice(0, maxText - size);
        chunks.push(part);
        size += part.length;
        if (part.length < value.length) break;
      }
    } finally {
      reader.cancel().catch(() => {});
    }
    return textBody(new TextDecoder().decode(concat(chunks)));
  }

  function concat(chunks) {
    const result = new Uint8Array(chunks.reduce((total, item) => total + item.length, 0));
    let offset = 0;
    for (const chunk of chunks) { result.set(chunk, offset); offset += chunk.length; }
    return result;
  }

  const originalFetch = window.fetch;
  window.fetch = async function (input, init = {}) {
    const request = input instanceof Request ? input : null;
    const url = cleanUrl(request?.url || input);
    const method = String(init.method || request?.method || "GET").toUpperCase();
    const started = performance.now();
    const requestHeaders = headersFrom(init.headers ?? request?.headers);
    let requestBody = typeof init.body === "string" ? textBody(init.body) : null;
    try {
      if (request && !init.body && /^(GET|HEAD)$/i.test(method) === false) {
        requestBody = textBody(await request.clone().text());
      }
    } catch {}
    try {
      const response = await originalFetch.apply(this, arguments);
      let body = null;
      try { body = await responseText(response.clone()); } catch {}
      const responseHeaders = headersFrom(response.headers);
      send("network", {
        source: "fetch",
        url,
        method,
        status: response.status,
        ok: response.ok,
        duration_ms: Math.round(performance.now() - started),
        request_headers: requestHeaders,
        request_body: requestBody,
        response_headers: responseHeaders,
        response_body: body,
        timestamp: new Date().toISOString(),
      });
      return response;
    } catch (error) {
      send("network", {
        source: "fetch", url, method, error: String(error), duration_ms: Math.round(performance.now() - started),
        request_headers: requestHeaders, request_body: requestBody, timestamp: new Date().toISOString(),
      });
      throw error;
    }
  };

  const xhrOpen = XMLHttpRequest.prototype.open;
  const xhrSend = XMLHttpRequest.prototype.send;
  const xhrHeader = XMLHttpRequest.prototype.setRequestHeader;
  XMLHttpRequest.prototype.open = function (method, url) {
    this.__maiusRecorder = { method: String(method).toUpperCase(), url: cleanUrl(url), headers: [] };
    return xhrOpen.apply(this, arguments);
  };
  XMLHttpRequest.prototype.setRequestHeader = function (name, value) {
    if (this.__maiusRecorder && !sensitive.test(name)) {
      this.__maiusRecorder.headers.push({ name, value: String(value).slice(0, 1000) });
    }
    return xhrHeader.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function (body) {
    const started = performance.now();
    this.addEventListener("loadend", () => {
      let responseBody = null;
      try {
        if (this.responseType === "json") {
          responseBody = JSON.stringify(clean(this.response));
        } else if ((this.responseType === "" || this.responseType === "text") && this.responseText.length <= maxText * 4) {
          responseBody = textBody(this.responseText);
        }
      } catch {}
      send("network", {
        source: "xhr",
        ...this.__maiusRecorder,
        status: this.status,
        duration_ms: Math.round(performance.now() - started),
        request_body: typeof body === "string" ? textBody(body) : null,
        response_body: responseBody,
        timestamp: new Date().toISOString(),
      });
    }, { once: true });
    return xhrSend.apply(this, arguments);
  };

  for (const level of ["log", "info", "warn", "error", "debug"]) {
    const original = console[level];
    console[level] = function (...args) {
      send("console", {
        level,
        message: args.map((arg) => {
          try { return clean(typeof arg === "string" ? textBody(arg) : JSON.parse(JSON.stringify(arg))); }
          catch { return textBody(arg?.stack || arg?.message || String(arg)); }
        }),
        timestamp: new Date().toISOString(),
        url: cleanUrl(location.href),
      });
      return original.apply(this, args);
    };
  }

  window.addEventListener("error", (event) => send("console", {
    level: "error",
    message: [textBody(event.message || "Error")],
    filename: cleanUrl(event.filename || location.href),
    line: event.lineno,
    column: event.colno,
    timestamp: new Date().toISOString(),
  }));
  window.addEventListener("unhandledrejection", (event) => send("console", {
    level: "error",
    message: [textBody(`Unhandled rejection: ${String(event.reason).slice(0, maxText)}`)],
    timestamp: new Date().toISOString(),
    url: cleanUrl(location.href),
  }));
})();
