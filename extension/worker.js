const api = globalThis.browser ?? chrome;
const captureKey = "activeCapture";
let writeQueue = Promise.resolve();
let recorderPort = null;
const requestStarts = new Map();
const database = new Promise((resolve, reject) => {
  const request = indexedDB.open("maius-bug-recorder", 1);
  request.onupgradeneeded = () => {
    const store = request.result.createObjectStore("logs", { keyPath: "id", autoIncrement: true });
    store.createIndex("captureId", "captureId");
    store.createIndex("captureKind", ["captureId", "kind"]);
  };
  request.onsuccess = () => resolve(request.result);
  request.onerror = () => reject(request.error);
});

function isSensitive(name) {
  return /authorization|cookie|set-cookie|password|passwd|secret|token|api[-_]?key|session/i.test(name);
}

function sanitizeUrl(value) {
  try {
    const url = new URL(value);
    for (const key of [...url.searchParams.keys()]) {
      if (isSensitive(key)) url.searchParams.set(key, "[REDACTED]");
    }
    return url.toString();
  } catch {
    return value;
  }
}

function safeHeaders(headers = []) {
  return headers
    .filter(({ name }) => !isSensitive(name))
    .map(({ name, value }) => ({
      name,
      value: /^(?:referer|location)$/i.test(name) ? sanitizeUrl(value) : String(value).slice(0, 2000),
    }));
}

function requestBody(body) {
  if (!body) return null;
  if (body.formData) {
    return Object.fromEntries(Object.entries(body.formData).map(([key, values]) => [
      key,
      isSensitive(key) ? "[REDACTED]" : values.map((value) => String(value).slice(0, 4096)),
    ]));
  }
  if (body.raw) return "[binary request body omitted]";
  return null;
}

function appendLog(kind, entry, tabId) {
  writeQueue = writeQueue.then(async () => {
    const { [captureKey]: capture } = await api.storage.local.get(captureKey);
    if (!capture?.active || capture.paused || capture.tabId !== tabId) return;
    const db = await database;
    await new Promise((resolve, reject) => {
      const tx = db.transaction("logs", "readwrite");
      const store = tx.objectStore("logs");
      const index = store.index("captureKind");
      const key = IDBKeyRange.only([capture.id, kind]);
      const count = index.count(key);
      count.onsuccess = () => {
        const add = () => store.add({ captureId: capture.id, kind, entry });
        if (count.result < 1000) {
          add();
          return;
        }
        const oldest = index.openCursor(key);
        oldest.onsuccess = () => {
          oldest.result?.delete();
          add();
        };
      };
      tx.oncomplete = resolve;
      tx.onerror = () => reject(tx.error);
    });
  }).catch(() => {});
  return writeQueue;
}

async function readAndClearLogs(captureId) {
  const db = await database;
  return new Promise((resolve, reject) => {
    const tx = db.transaction("logs", "readwrite");
    const store = tx.objectStore("logs");
    const request = store.index("captureId").getAll(captureId);
    let rows = [];
    request.onsuccess = () => {
      rows = request.result;
      const cursorRequest = store.index("captureId").openCursor(captureId);
      cursorRequest.onsuccess = () => {
        const cursor = cursorRequest.result;
        if (cursor) {
          cursor.delete();
          cursor.continue();
        }
      };
    };
    tx.oncomplete = () => resolve({
      console: rows.filter((row) => row.kind === "console").map((row) => row.entry),
      network: rows.filter((row) => row.kind === "network").map((row) => row.entry),
    });
    tx.onerror = () => reject(tx.error);
  });
}

function matchingCapture(tabId, callback, includePaused = false) {
  api.storage.local.get(captureKey).then(({ [captureKey]: capture }) => {
    if (capture?.active && (includePaused || !capture.paused) && capture.tabId === tabId) callback(capture);
  });
}

api.webRequest.onBeforeRequest.addListener((details) => {
  matchingCapture(details.tabId, () => {
    requestStarts.set(details.requestId, details.timeStamp);
    appendLog("network", {
      source: "browser",
      event: "request",
      url: sanitizeUrl(details.url),
      method: details.method,
      type: details.type,
      request_body: requestBody(details.requestBody),
      timestamp: details.timeStamp,
    }, details.tabId);
  });
}, { urls: ["<all_urls>"] }, ["requestBody"]);

api.webRequest.onBeforeSendHeaders.addListener((details) => {
  const headers = safeHeaders(details.requestHeaders);
  if (headers.length) matchingCapture(details.tabId, () => appendLog("network", {
    source: "browser",
    event: "request_headers",
    url: sanitizeUrl(details.url),
    request_id: details.requestId,
    headers,
    timestamp: details.timeStamp,
  }, details.tabId));
}, { urls: ["<all_urls>"] }, ["requestHeaders"]);

api.webRequest.onHeadersReceived.addListener((details) => {
  matchingCapture(details.tabId, () => appendLog("network", {
    source: "browser",
    event: "response_headers",
    url: sanitizeUrl(details.url),
    request_id: details.requestId,
    status: details.statusCode,
    headers: safeHeaders(details.responseHeaders),
    timestamp: details.timeStamp,
  }, details.tabId));
}, { urls: ["<all_urls>"] }, ["responseHeaders"]);

api.webRequest.onCompleted.addListener((details) => {
  const startedAt = requestStarts.get(details.requestId) ?? details.timeStamp;
  requestStarts.delete(details.requestId);
  matchingCapture(details.tabId, () => appendLog("network", {
    source: "browser",
    event: "completed",
    url: sanitizeUrl(details.url),
    request_id: details.requestId,
    method: details.method,
    status: details.statusCode,
    duration_ms: Math.max(0, Math.round(details.timeStamp - startedAt)),
    timestamp: details.timeStamp,
  }, details.tabId));
}, { urls: ["<all_urls>"] });

api.webRequest.onErrorOccurred.addListener((details) => {
  requestStarts.delete(details.requestId);
  matchingCapture(details.tabId, () => appendLog("network", {
    source: "browser",
    event: "failed",
    url: sanitizeUrl(details.url),
    request_id: details.requestId,
    method: details.method,
    error: details.error,
    timestamp: details.timeStamp,
  }, details.tabId));
}, { urls: ["<all_urls>"] });

api.runtime.onMessage.addListener((message, sender, sendResponse) => {
  const run = async () => {
    if (message.type === "capture:start") {
      const { [captureKey]: previous } = await api.storage.local.get(captureKey);
      if (previous?.id) await readAndClearLogs(previous.id);
      await api.storage.local.set({
        [captureKey]: {
          active: true,
          paused: false,
          tabId: message.tabId,
          recorderTabId: sender.tab?.id,
          id: crypto.randomUUID(),
          startedAt: Date.now(),
          pausedAt: null,
          pausedDuration: 0,
          micAvailable: Boolean(message.micAvailable),
          micEnabled: Boolean(message.micAvailable),
        },
      });
      try {
        await injectCapture(message.tabId);
      } catch (error) {
        await api.storage.local.set({
          [captureKey]: { active: false, tabId: message.tabId, id: null },
        });
        throw error;
      }
      return { ok: true };
    }

    if (message.type === "capture:entry") {
      const tabId = sender.tab?.id;
      if (tabId == null || !["console", "network"].includes(message.kind)) return { ok: false };
      const entry = message.entry;
      return appendLog(message.kind, entry, tabId).then(() => ({ ok: true }));
    }

    if (message.type === "capture:status") {
      const { [captureKey]: capture } = await api.storage.local.get(captureKey);
      if (!capture?.active || capture.tabId !== sender.tab?.id) return { active: false };
      return {
        active: true,
        paused: capture.paused,
        startedAt: capture.startedAt,
        pausedAt: capture.pausedAt,
        pausedDuration: capture.pausedDuration,
        micAvailable: capture.micAvailable,
        micEnabled: capture.micEnabled,
      };
    }

    if (message.type === "capture:control") {
      const { [captureKey]: capture } = await api.storage.local.get(captureKey);
      if (!capture?.active || capture.tabId !== sender.tab?.id) return { ok: false };
      if (message.action === "pause" && !capture.paused) {
        capture.paused = true;
        capture.pausedAt = Date.now();
      } else if (message.action === "resume" && capture.paused) {
        capture.paused = false;
        capture.pausedDuration += Date.now() - capture.pausedAt;
        capture.pausedAt = null;
      } else if (message.action === "mic" && capture.micAvailable) {
        capture.micEnabled = !capture.micEnabled;
      } else if (!["stop", "draw"].includes(message.action)) {
        return { ok: false };
      }
      await api.storage.local.set({ [captureKey]: capture });
      try {
        recorderPort?.postMessage({ type: "recording:control", action: message.action, micEnabled: capture.micEnabled });
      } catch {}
      return { ok: true, paused: capture.paused, micEnabled: capture.micEnabled };
    }

    if (message.type === "capture:finish") {
      await writeQueue;
      const { [captureKey]: capture } = await api.storage.local.get(captureKey);
      if (!capture || capture.tabId !== message.tabId) return { console: [], network: [] };
      capture.active = false;
      await api.storage.local.set({ [captureKey]: capture });
      api.tabs.sendMessage(capture.tabId, { type: "toolbar:remove" }).catch(() => {});
      return capture.id ? readAndClearLogs(capture.id) : { console: [], network: [] };
    }
    return { ok: false };
  };

  run().then(sendResponse).catch((error) => sendResponse({ ok: false, error: error.message }));
  return true;
});

api.runtime.onConnect.addListener((port) => {
  if (port.name !== "recording-controls") return;
  recorderPort = port;
  port.onMessage.addListener(() => {});
  port.onDisconnect.addListener(() => {
    if (recorderPort === port) recorderPort = null;
  });
});

async function injectCapture(tabId) {
  await api.scripting.executeScript({
    target: { tabId },
    world: "MAIN",
    files: ["capture-main.js"],
    injectImmediately: true,
  });
  await api.scripting.executeScript({
    target: { tabId },
    world: "ISOLATED",
    files: ["capture-bridge.js"],
    injectImmediately: true,
  });
  await api.scripting.executeScript({
    target: { tabId },
    world: "ISOLATED",
    files: ["capture-ui.js"],
    injectImmediately: true,
  });
}

api.tabs.onUpdated.addListener((tabId, changeInfo) => {
  if (changeInfo.status !== "loading") return;
  matchingCapture(tabId, () => injectCapture(tabId).catch(() => {}), true);
});
