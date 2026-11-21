const api = globalThis.browser ?? chrome;
const params = new URLSearchParams(location.search);
const targetTabId = Number(params.get("tabId"));
const targetLabel = document.querySelector("#target");
const titleInput = document.querySelector("#title");
const bugSelect = document.querySelector("#bug");
const bugPanel = document.querySelector("#newBug");
const startButton = document.querySelector("#start");
const stopButton = document.querySelector("#stop");
const retryButton = document.querySelector("#retry");
const status = document.querySelector("#status");
const includeMic = document.querySelector("#includeMic");
const recorderPort = api.runtime.connect({ name: "recording-controls" });

let apiBase = "";
let extensionToken = "";
let mediaRecorder = null;
let stream = null;
let microphoneStream = null;
let chunks = [];
let startedAt = 0;
let recordedBlob = null;
let captureLogs = { console: [], network: [] };
let uploading = false;
let captureStarted = false;
let heartbeat = null;

recorderPort.onMessage.addListener((message) => {
  if (message.type !== "recording:control") return;
  if (message.action === "stop") stopRecording();
  if (message.action === "pause" && mediaRecorder?.state === "recording") {
    mediaRecorder.pause();
    setStatus("Grabación pausada.");
  }
  if (message.action === "resume" && mediaRecorder?.state === "paused") {
    mediaRecorder.resume();
    setStatus("Grabando. Detén la grabación cuando hayas reproducido el problema.");
  }
  if (message.action === "mic" && stream?.getAudioTracks().length) {
    stream.getAudioTracks().forEach((track) => { track.enabled = Boolean(message.micEnabled); });
  }
});
function startWorkerHeartbeat() {
  heartbeat = window.setInterval(() => {
    try { recorderPort.postMessage({ type: "heartbeat" }); } catch {}
  }, 20000);
}

function stopWorkerHeartbeat() {
  if (heartbeat) window.clearInterval(heartbeat);
  heartbeat = null;
}

function setStatus(message, failed = false) {
  status.textContent = message;
  status.classList.toggle("error", failed);
}

async function request(path, options = {}) {
  const response = await fetch(`${apiBase}${path}`, {
    ...options,
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${extensionToken}`,
      ...options.headers,
    },
  });
  const body = await response.text();
  let data;
  try {
    data = body ? JSON.parse(body) : {};
  } catch {
    if (response.status === 413) {
      throw new Error("El servidor rechazó el video por tamaño (HTTP 413). Reconstruye/reinicia PHP y Nginx con los límites de carga de extension/README.md.");
    }
    throw new Error(`La API respondió HTTP ${response.status} sin JSON. Revisa la URL y los logs del backend.`);
  }
  if (!response.ok) throw new Error(data?.error || "Error de comunicación con Maius.");
  return data;
}

async function init() {
  const saved = await api.storage.local.get(["apiBase", "extensionToken"]);
  apiBase = (saved.apiBase || "").replace(/\/+$/, "");
  extensionToken = saved.extensionToken || "";
  if (!apiBase || !extensionToken || !Number.isInteger(targetTabId)) {
    setStatus("Configura la extensión y vuelve a iniciar desde la pestaña que quieres grabar.", true);
    startButton.disabled = true;
    return;
  }

  try {
    const [session, bugResponse, target] = await Promise.all([
      request("/extension/session"),
      request("/extension/bugs"),
      api.tabs.get(targetTabId),
    ]);
    targetLabel.textContent = `Grabando: ${target.title || target.url}`;
    titleInput.value = target.title ? `Problema en ${target.title}`.slice(0, 150) : "";
    for (const bug of bugResponse.data || []) {
      const option = document.createElement("option");
      option.value = bug.id;
      option.textContent = `#${bug.id} · ${bug.title}`;
      bugSelect.append(option);
    }
    setStatus(`Sesión de ${session.data.name} activa. Capturaremos pantalla, consola y tráfico de esta pestaña.`);
  } catch (error) {
    setStatus(error.message, true);
    startButton.disabled = true;
  }
}

async function startRecording() {
  startButton.disabled = true;
  try {
    stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
    if (includeMic.checked) {
      microphoneStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      microphoneStream.getAudioTracks().forEach((track) => stream.addTrack(track));
    }
    const capture = await api.runtime.sendMessage({
      type: "capture:start",
      tabId: targetTabId,
      micAvailable: Boolean(stream.getAudioTracks().length),
    });
    if (!capture?.ok) throw new Error(capture?.error || "No se pudo activar la captura de consola y red.");
    captureStarted = true;
    startWorkerHeartbeat();
    const mimeType = ["video/webm;codecs=vp9", "video/webm;codecs=vp8", "video/webm"]
      .find((type) => MediaRecorder.isTypeSupported(type));
    mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
    chunks = [];
    mediaRecorder.addEventListener("dataavailable", (event) => {
      if (event.data.size) chunks.push(event.data);
    });
    mediaRecorder.addEventListener("stop", finalizeRecording, { once: true });
    mediaRecorder.start(1000);
    startedAt = Date.now();
    startButton.hidden = true;
    stopButton.hidden = false;
    setStatus("Grabando. Detén la grabación cuando hayas reproducido el problema.");
    stream.getVideoTracks()[0].addEventListener("ended", () => {
      if (mediaRecorder?.state === "recording" || mediaRecorder?.state === "paused") mediaRecorder.stop();
    }, { once: true });
  } catch (error) {
    if (captureStarted) {
      await api.runtime.sendMessage({ type: "capture:finish", tabId: targetTabId }).catch(() => {});
      captureStarted = false;
    }
    stopWorkerHeartbeat();
    stream?.getTracks().forEach((track) => track.stop());
    microphoneStream?.getTracks().forEach((track) => track.stop());
    stream = null;
    microphoneStream = null;
    startButton.disabled = false;
    setStatus(error.message || "No se pudo iniciar la captura.", true);
  }
}

async function finalizeRecording() {
  await new Promise((resolve) => window.setTimeout(resolve, 200));
  const response = await api.runtime.sendMessage({ type: "capture:finish", tabId: targetTabId });
  captureStarted = false;
  stopWorkerHeartbeat();
  captureLogs = response || { console: [], network: [] };
  recordedBlob = new Blob(chunks, { type: mediaRecorder?.mimeType || "video/webm" });
  stream?.getTracks().forEach((track) => track.stop());
  microphoneStream?.getTracks().forEach((track) => track.stop());
  stream = null;
  microphoneStream = null;
  mediaRecorder = null;
  stopButton.hidden = true;
  setStatus("Grabación lista. Guardando video y registros…");
  await uploadRecording();
}

async function uploadRecording() {
  if (!recordedBlob || uploading) return;
  uploading = true;
  retryButton.hidden = true;
  startButton.disabled = true;
  try {
    let bugId = bugSelect.value;
    if (!bugPanel.hidden) {
      const title = document.querySelector("#bugTitle").value.trim();
      const description = document.querySelector("#bugDescription").value.trim();
      if (!title || !description) throw new Error("Completa el título y la descripción del bug.");
      const created = await request("/extension/bugs", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          title,
          description,
          severity: document.querySelector("#severity").value,
        }),
      });
      bugId = created.data.id;
      bugSelect.add(new Option(`#${bugId} · ${title}`, bugId, true, true));
      bugPanel.hidden = true;
      bugSelect.disabled = false;
      document.querySelector("#newBugToggle").textContent = "Crear bug nuevo";
    }

    const form = new FormData();
    form.append("video", recordedBlob, `recording.${recordedBlob.type.includes("mp4") ? "mp4" : "webm"}`);
    if (titleInput.value.trim()) form.append("title", titleInput.value.trim());
    form.append("duration_ms", String(Math.max(0, Date.now() - startedAt)));
    if (bugId) form.append("bug_id", bugId);
    form.append("console_logs", JSON.stringify(captureLogs.console || []));
    form.append("network_logs", JSON.stringify(captureLogs.network || []));
    await request("/extension/recordings", { method: "POST", body: form });

    recordedBlob = null;
    chunks = [];
    setStatus("Guardado en Maius. Ya puedes cerrar esta pestaña.");
  } catch (error) {
    setStatus(`${error.message} La grabación se conserva en esta pestaña para reintentar.`, true);
    retryButton.hidden = false;
  } finally {
    uploading = false;
  }
}

document.querySelector("#newBugToggle").addEventListener("click", () => {
  bugPanel.hidden = !bugPanel.hidden;
  document.querySelector("#newBugToggle").textContent = bugPanel.hidden ? "Crear bug nuevo" : "Usar un bug existente";
  bugSelect.disabled = !bugPanel.hidden;
});
startButton.addEventListener("click", startRecording);
function stopRecording() {
  stopButton.disabled = true;
  if (mediaRecorder?.state === "recording" || mediaRecorder?.state === "paused") mediaRecorder.stop();
}
stopButton.addEventListener("click", stopRecording);
retryButton.addEventListener("click", uploadRecording);

init();
