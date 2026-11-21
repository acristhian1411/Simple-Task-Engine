const api = globalThis.browser ?? chrome;
const apiInput = document.querySelector("#api");
const tokenInput = document.querySelector("#token");
const status = document.querySelector("#status");
const connectButton = document.querySelector("#connect");
const startButton = document.querySelector("#start");

async function loadSaved() {
  const { apiBase = "", extensionToken = "" } = await api.storage.local.get(["apiBase", "extensionToken"]);
  apiInput.value = apiBase;
  tokenInput.value = extensionToken;
  if (apiBase && extensionToken) await verify();
  else status.textContent = "Conecta la extensión desde la sección Grabaciones de Maius.";
}

async function verify() {
  const apiBase = apiInput.value.trim().replace(/\/+$/, "");
  const extensionToken = tokenInput.value.trim();
  status.classList.remove("error");
  startButton.disabled = true;

  if (!apiBase || !extensionToken) {
    status.textContent = "Ingresa la URL de la API y el token.";
    return false;
  }

  try {
    const response = await fetch(`${apiBase}/extension/session`, {
      headers: { Authorization: `Bearer ${extensionToken}`, Accept: "application/json" },
    });
    const body = await response.json();
    if (!response.ok) throw new Error(body?.error || "No se pudo validar la sesión.");
    await api.storage.local.set({ apiBase, extensionToken });
    status.textContent = `Conectado como ${body.data.name}`;
    startButton.disabled = false;
    return true;
  } catch (error) {
    status.textContent = error.message || "Error de conexión.";
    status.classList.add("error");
    return false;
  }
}

connectButton.addEventListener("click", verify);
startButton.addEventListener("click", async () => {
  if (!(await verify())) return;
  const [tab] = await api.tabs.query({ active: true, currentWindow: true });
  if (!tab?.id || !/^https?:/.test(tab.url || "")) {
    status.textContent = "Abre primero la página que quieres grabar.";
    status.classList.add("error");
    return;
  }
  await api.tabs.create({
    url: `${api.runtime.getURL("recorder.html")}?tabId=${tab.id}`,
  });
  window.close();
});

loadSaved();
