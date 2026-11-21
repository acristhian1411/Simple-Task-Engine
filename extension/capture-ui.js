(() => {
  if (document.querySelector("#maius-bug-recorder-controls")) return;
  window.__maiusCaptureUiInstalled = true;

  const api = globalThis.browser ?? chrome;
  api.runtime.sendMessage({ type: "capture:status" }).then((state) => {
    if (!state?.active) {
      delete window.__maiusCaptureUiInstalled;
      return;
    }

    const host = document.createElement("div");
    host.id = "maius-bug-recorder-controls";
    const root = host.attachShadow({ mode: "open" });
    root.innerHTML = `
      <style>
        :host { all: initial; position: fixed; z-index: 2147483647; inset: 0; pointer-events: none; }
        #bar { position: fixed; z-index: 2; left: 50%; bottom: 26px; transform: translateX(-50%); display: flex; align-items: center; gap: 5px; padding: 7px 9px; border: 1px solid #475569; border-radius: 15px; color: #f8fafc; background: #111827ed; box-shadow: 0 8px 30px #0008; font: 13px system-ui, sans-serif; pointer-events: auto; }
        button { display: grid; place-items: center; min-width: 34px; height: 34px; padding: 0 8px; border: 0; border-radius: 8px; color: inherit; background: transparent; font: 700 17px system-ui, sans-serif; cursor: pointer; }
        button:hover, button.active { background: #334155; }
        button:focus-visible { outline: 2px solid #a5b4fc; outline-offset: 2px; }
        #stop { color: #fb7185; font-size: 18px; }
        #clock { min-width: 50px; text-align: center; font-variant-numeric: tabular-nums; }
        #divider { width: 1px; height: 22px; background: #475569; }
        #draw { position: fixed; z-index: 1; inset: 0; width: 100vw; height: 100vh; pointer-events: none; }
        #draw.enabled { pointer-events: auto; cursor: crosshair; }
      </style>
      <canvas id="draw"></canvas>
      <div id="bar" role="toolbar" aria-label="Controles de grabación">
        <button id="stop" title="Detener y guardar" aria-label="Detener y guardar">■</button>
        <button id="pause" title="Pausar" aria-label="Pausar">Ⅱ</button>
        <span id="clock" aria-live="off">00:00</span>
        <span id="divider"></span>
        <button id="mic" title="Micrófono" aria-label="Micrófono">🎙</button>
        <button id="pen" title="Dibujar sobre la página" aria-label="Dibujar sobre la página">✎</button>
        <button id="clear" title="Borrar anotaciones" aria-label="Borrar anotaciones">▦</button>
      </div>
    `;
    document.documentElement.append(host);

    const $ = (selector) => root.querySelector(selector);
    const canvas = $("#draw");
    const context = canvas.getContext("2d");
    let paused = Boolean(state.paused);
    let micEnabled = Boolean(state.micEnabled);
    let drawing = false;
    let drawingNow = false;

    function updatePauseButton() {
      const button = $("#pause");
      button.textContent = paused ? "▶" : "Ⅱ";
      button.title = paused ? "Reanudar" : "Pausar";
      button.setAttribute("aria-label", button.title);
      button.classList.toggle("active", paused);
    }

    function updateMicButton() {
      const button = $("#mic");
      button.textContent = micEnabled ? "🎙" : "♩";
      button.title = state.micAvailable
        ? (micEnabled ? "Silenciar micrófono" : "Activar micrófono")
        : "Activa el micrófono antes de grabar";
      button.setAttribute("aria-label", button.title);
      button.disabled = !state.micAvailable;
      button.style.opacity = button.disabled ? ".45" : "1";
    }

    function resizeCanvas() {
      const ratio = window.devicePixelRatio || 1;
      canvas.width = Math.round(window.innerWidth * ratio);
      canvas.height = Math.round(window.innerHeight * ratio);
      context.setTransform(ratio, 0, 0, ratio, 0, 0);
    }

    function elapsedSeconds() {
      const activePause = paused && state.pausedAt ? Date.now() - state.pausedAt : 0;
      return Math.max(0, Math.floor((Date.now() - state.startedAt - state.pausedDuration - activePause) / 1000));
    }

    function updateClock() {
      const seconds = elapsedSeconds();
      $("#clock").textContent = `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`;
    }

    $("#stop").addEventListener("click", () => {
      api.runtime.sendMessage({ type: "capture:control", action: "stop" });
    });
    $("#pause").addEventListener("click", () => {
      paused = !paused;
      if (paused) state.pausedAt = Date.now();
      else {
        state.pausedDuration += Date.now() - state.pausedAt;
        state.pausedAt = null;
      }
      updatePauseButton();
      api.runtime.sendMessage({ type: "capture:control", action: paused ? "pause" : "resume" });
    });
    $("#mic").addEventListener("click", () => {
      if (!state.micAvailable) return;
      micEnabled = !micEnabled;
      updateMicButton();
      api.runtime.sendMessage({ type: "capture:control", action: "mic" });
    });
    $("#pen").addEventListener("click", (event) => {
      drawing = !drawing;
      canvas.classList.toggle("enabled", drawing);
      event.currentTarget.classList.toggle("active", drawing);
    });
    $("#clear").addEventListener("click", () => context.clearRect(0, 0, window.innerWidth, window.innerHeight));
    canvas.addEventListener("pointerdown", (event) => {
      drawingNow = true;
      canvas.setPointerCapture(event.pointerId);
      context.beginPath();
      context.moveTo(event.clientX, event.clientY);
    });
    canvas.addEventListener("pointermove", (event) => {
      if (!drawingNow) return;
      context.strokeStyle = "#fb415e";
      context.lineWidth = 4;
      context.lineCap = "round";
      context.lineTo(event.clientX, event.clientY);
      context.stroke();
    });
    canvas.addEventListener("pointerup", () => { drawingNow = false; });
    canvas.addEventListener("pointercancel", () => { drawingNow = false; });
    window.addEventListener("resize", resizeCanvas);

    resizeCanvas();
    updatePauseButton();
    updateMicButton();
    updateClock();
    const clock = window.setInterval(updateClock, 500);
    api.runtime.onMessage.addListener((message) => {
      if (message.type !== "toolbar:remove") return;
      window.clearInterval(clock);
      host.remove();
      delete window.__maiusCaptureUiInstalled;
      window.removeEventListener("resize", resizeCanvas);
    });
  }).catch(() => { delete window.__maiusCaptureUiInstalled; });
})();
