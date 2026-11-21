import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import vm from "node:vm";
import test from "node:test";

test("capture redacts credentials from console and fetch logs", async () => {
  const source = await readFile(new URL("./capture-main.js", import.meta.url), "utf8");
  const sent = [];
  const page = {
    location: { href: "https://example.test/page?access_token=private" },
    postMessage: (data) => sent.push(data),
    addEventListener: () => {},
    fetch: async () => ({
      status: 200,
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      clone: () => ({ headers: new Headers({ "content-type": "application/json" }), body: null }),
    }),
  };
  const pageConsole = Object.fromEntries(
    ["log", "info", "warn", "error", "debug"].map((level) => [level, () => {}]),
  );
  class FakeXhr {}
  FakeXhr.prototype.open = () => {};
  FakeXhr.prototype.send = () => {};
  FakeXhr.prototype.setRequestHeader = () => {};

  vm.runInNewContext(source, {
    window: page,
    location: page.location,
    console: pageConsole,
    XMLHttpRequest: FakeXhr,
    URL,
    Headers,
    Request,
    TextDecoder,
    Blob,
    Uint8Array,
    Date,
    JSON,
    Object,
    Array,
    String,
    RegExp,
    Promise,
    performance: { now: () => 1 },
  });

  pageConsole.error("Authorization: Bearer top-secret");
  await page.fetch("https://example.test/api?token=private", {
    method: "POST",
    headers: { Authorization: "Bearer top-secret", "Content-Type": "application/json" },
    body: JSON.stringify({ password: "top-secret", comment: "kept" }),
  });

  const consoleEntry = sent.find((item) => item.kind === "console").entry;
  const networkEntry = sent.find((item) => item.kind === "network").entry;
  assert.match(consoleEntry.message[0], /\[REDACTED\]/);
  assert.doesNotMatch(consoleEntry.message[0], /top-secret/);
  assert.match(networkEntry.url, /token=%5BREDACTED%5D/);
  assert.deepEqual(
    JSON.parse(JSON.stringify(networkEntry.request_headers)).map((header) => header.name),
    ["content-type"],
  );
  assert.match(networkEntry.request_body, /\[REDACTED\]/);
  assert.doesNotMatch(networkEntry.request_body, /top-secret/);
});
