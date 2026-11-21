if (!window.__maiusCaptureBridgeInstalled) {
  window.__maiusCaptureBridgeInstalled = true;
  const api = globalThis.browser ?? chrome;
  window.addEventListener("message", (event) => {
    if (event.source !== window || event.data?.source !== "maius-bug-recorder") return;
    const { kind, entry } = event.data;
    if (!["console", "network"].includes(kind)) return;
    api.runtime.sendMessage({ type: "capture:entry", kind, entry });
  });

  api.runtime.onMessage.addListener((message) => {
    if (message.type === "toolbar:remove") {
      document.querySelector("#maius-bug-recorder-controls")?.remove();
    }
  });
}
