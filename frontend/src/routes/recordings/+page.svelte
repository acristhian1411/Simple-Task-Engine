<script>
  import { onDestroy, onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getAllBugs } from "$lib/api/bugs.js";
  import {
    deleteRecording,
    getAllRecordings,
    getExtensionTokens,
    getRecordingFile,
    getRecordingLogs,
    attachRecording,
    detachRecording,
    issueExtensionToken,
    revokeExtensionToken,
  } from "$lib/api/recordings.js";

  let loading = $state(true);
  let error = $state("");
  let recordings = $state([]);
  let bugs = $state([]);
  let tokens = $state([]);
  let selected = $state(null);
  let logs = $state({ console: [], network: [] });
  let activeTab = $state("info");
  let selectedBugId = $state("");
  let videoUrl = $state("");
  let newToken = $state("");
  let tokenBusy = $state(false);

  function unwrap(payload) {
    return payload?.data?.data ?? payload?.data ?? payload;
  }

  async function load() {
    loading = true;
    error = "";
    try {
      if (!(await refreshMe())) return goto("/login");
      const [allRecordings, allBugs, tokenResponse] = await Promise.all([
        getAllRecordings(),
        getAllBugs(),
        getExtensionTokens(),
      ]);
      recordings = allRecordings;
      bugs = allBugs;
      tokens = unwrap(tokenResponse)?.data ?? unwrap(tokenResponse) ?? [];
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando grabaciones";
    } finally {
      loading = false;
    }
  }

  async function openRecording(recording) {
    selected = recording;
    activeTab = "info";
    error = "";
    selectedBugId = recording.recordable_type?.toLowerCase().includes("bugs")
      ? String(recording.recordable_id)
      : "";
    logs = { console: [], network: [] };
    if (videoUrl) URL.revokeObjectURL(videoUrl);
    videoUrl = "";
    try {
      const [file, logData] = await Promise.all([
        getRecordingFile(recording.id),
        getRecordingLogs(recording.id),
      ]);
      videoUrl = URL.createObjectURL(file);
      logs = logData;
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "No se pudo abrir la grabación";
    }
  }

  function backToList() {
    selected = null;
    if (videoUrl) URL.revokeObjectURL(videoUrl);
    videoUrl = "";
  }

  function formatTime(value) {
    if (!value) return "—";
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? "—" : date.toLocaleTimeString();
  }

  function formatDuration(value) {
    if (value == null) return "—";
    const seconds = Math.floor(value / 1000);
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, "0")}`;
  }

  function networkTitle(entry) {
    const method = entry.method || entry.source?.toUpperCase() || "REQUEST";
    const status = entry.status ? ` · ${entry.status}` : "";
    const event = entry.event ? ` · ${entry.event}` : "";
    return `${method}${status}${event} · ${entry.url || "URL desconocida"}`;
  }

  async function saveAssociation() {
    if (!selected) return;
    try {
      const result = selectedBugId
        ? await attachRecording(selected.id, Number(selectedBugId))
        : await detachRecording(selected.id);
      selected = {
        ...selected,
        ...unwrap(result),
        recordable: bugs.find((bug) => String(bug.id) === selectedBugId) ?? null,
      };
      recordings = recordings.map((item) => item.id === selected.id ? selected : item);
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "No se pudo actualizar la asociación";
    }
  }

  async function removeRecording(recording) {
    if (!confirm(`¿Eliminar la grabación "${recording.title || `#${recording.id}`}" y sus archivos?`)) return;
    try {
      await deleteRecording(recording.id);
      recordings = recordings.filter((item) => item.id !== recording.id);
      if (selected?.id === recording.id) {
        selected = null;
        if (videoUrl) URL.revokeObjectURL(videoUrl);
        videoUrl = "";
      }
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "No se pudo eliminar";
    }
  }

  async function createExtensionToken() {
    tokenBusy = true;
    error = "";
    try {
      const result = await issueExtensionToken("Browser extension");
      newToken = unwrap(result)?.token ?? "";
      await loadTokens();
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "No se pudo crear el token";
    } finally {
      tokenBusy = false;
    }
  }

  async function loadTokens() {
    const response = await getExtensionTokens();
    tokens = unwrap(response)?.data ?? unwrap(response) ?? [];
  }

  async function revokeToken(token) {
    if (!confirm(`¿Revocar el acceso "${token.label || `#${token.id}`}"?`)) return;
    try {
      await revokeExtensionToken(token.id);
      tokens = tokens.map((item) => item.id === token.id ? { ...item, revoked_at: new Date().toISOString() } : item);
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "No se pudo revocar el token";
    }
  }

  onMount(load);
  onDestroy(() => {
    if (videoUrl) URL.revokeObjectURL(videoUrl);
  });
</script>

<svelte:head>
  <title>Grabaciones | Maius Kanban</title>
</svelte:head>

<div class="mx-auto max-w-[1600px] space-y-6 p-6">
  {#if error}
    <div role="alert" class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-700 dark:text-red-300">{error}</div>
  {/if}

  {#if loading}
    <p class="text-sm text-slate-500">Cargando...</p>
  {:else if selected}
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3">
        <button class="rounded-lg border border-border-light px-3 py-2 text-sm hover:bg-slate-100 dark:border-border-dark dark:hover:bg-slate-800" onclick={backToList}>← Todas</button>
        <div class="min-w-0">
          <h1 class="truncate text-xl font-bold">{selected.title || `Grabación #${selected.id}`}</h1>
          <p class="text-xs text-slate-500">{selected.created_at ? new Date(selected.created_at).toLocaleString() : ""}</p>
        </div>
      </div>
      <button class="rounded-lg border border-red-500/30 px-3 py-2 text-sm text-red-600 hover:bg-red-500/10" onclick={() => removeRecording(selected)}>Eliminar grabación</button>
    </header>

    <div class="grid min-h-[70vh] overflow-hidden rounded-xl border border-slate-800 bg-slate-950 text-slate-100 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,0.9fr)]">
      <section class="flex min-h-[50vh] flex-col border-b border-slate-800 xl:border-b-0 xl:border-r">
        <div class="flex flex-1 items-center justify-center bg-black p-3">
          {#if videoUrl}
            <video class="max-h-[78vh] w-full bg-black object-contain" src={videoUrl} controls playsinline></video>
          {:else}
            <p class="text-sm text-slate-400">Cargando video…</p>
          {/if}
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 px-4 py-3 text-xs text-slate-400">
          <span>{selected.mime_type || "Video"} · {formatDuration(selected.duration_ms)}</span>
          <span>{selected.file_size_bytes ? `${(selected.file_size_bytes / 1048576).toFixed(1)} MB` : ""}</span>
        </div>
      </section>

      <aside class="flex min-h-[50vh] flex-col bg-[#111315]">
        <div class="flex border-b border-slate-800" role="tablist" aria-label="Datos de la grabación">
          {#each [
            { id: "info", label: "Info" },
            { id: "console", label: "Console" },
            { id: "network", label: "Network" },
          ] as tab}
            <button
              role="tab"
              aria-selected={activeTab === tab.id}
              class="border-b-2 px-4 py-3 text-sm {activeTab === tab.id ? 'border-emerald-400 text-white' : 'border-transparent text-slate-400 hover:text-slate-200'}"
              onclick={() => activeTab = tab.id}
            >
              {tab.label}
              {#if tab.id === "console"} <span class="ml-1 text-xs text-slate-500">{logs.console?.length ?? 0}</span>{/if}
              {#if tab.id === "network"} <span class="ml-1 text-xs text-slate-500">{logs.network?.length ?? 0}</span>{/if}
            </button>
          {/each}
        </div>

        {#if activeTab === "info"}
          <div class="space-y-5 overflow-auto p-5 text-sm" role="tabpanel">
            <div>
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Asociación</p>
              <label for="recording-bug" class="sr-only">Bug asociado</label>
              <div class="flex gap-2">
                <select id="recording-bug" bind:value={selectedBugId} class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white">
                  <option value="">Sin asociación</option>
                  {#each bugs as bug (bug.id)}
                    <option value={String(bug.id)}>#{bug.id} · {bug.title}</option>
                  {/each}
                </select>
                <button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500" onclick={saveAssociation}>Guardar</button>
              </div>
            </div>
            <dl class="divide-y divide-slate-800 rounded-lg border border-slate-800">
              <div class="flex justify-between gap-4 px-3 py-3"><dt class="text-slate-400">Estado</dt><dd>{selected.status || "—"}</dd></div>
              <div class="flex justify-between gap-4 px-3 py-3"><dt class="text-slate-400">Duración</dt><dd>{formatDuration(selected.duration_ms)}</dd></div>
              <div class="flex justify-between gap-4 px-3 py-3"><dt class="text-slate-400">Registrado por</dt><dd>{selected.recorded_by?.name || "—"}</dd></div>
              <div class="flex justify-between gap-4 px-3 py-3"><dt class="text-slate-400">Creado</dt><dd>{selected.created_at ? new Date(selected.created_at).toLocaleString() : "—"}</dd></div>
            </dl>
          </div>
        {:else if activeTab === "console"}
          <div class="flex-1 space-y-1 overflow-auto p-2" role="tabpanel">
            {#if logs.console?.length}
              {#each logs.console as entry, index}
                <article class="grid grid-cols-[52px_48px_minmax(0,1fr)] gap-2 rounded px-2 py-2 font-mono text-xs hover:bg-white/5 {entry.level === 'error' ? 'bg-red-950/30' : ''}">
                  <time class="text-slate-500">{formatTime(entry.timestamp)}</time>
                  <span class="uppercase {entry.level === 'error' || entry.level === 'warn' ? 'text-rose-400' : 'text-sky-400'}">{entry.level || "log"}</span>
                  <div class="min-w-0">
                    <pre class="whitespace-pre-wrap break-words">{Array.isArray(entry.message) ? entry.message.map((item) => typeof item === "string" ? item : JSON.stringify(item)).join(" ") : JSON.stringify(entry.message)}</pre>
                    {#if entry.url || entry.filename}<p class="mt-1 truncate text-slate-500">{entry.url || entry.filename}</p>{/if}
                  </div>
                </article>
              {/each}
            {:else}
              <p class="p-6 text-center text-sm text-slate-500">No se capturaron mensajes de consola.</p>
            {/if}
          </div>
        {:else}
          <div class="flex-1 space-y-1 overflow-auto p-2" role="tabpanel">
            {#if logs.network?.length}
              {#each logs.network as entry, index}
                <details class="group rounded border border-slate-800 bg-slate-900/60">
                  <summary class="grid cursor-pointer grid-cols-[52px_minmax(0,1fr)] gap-2 px-3 py-2 font-mono text-xs hover:bg-white/5">
                    <time class="text-slate-500">{formatTime(entry.timestamp)}</time>
                    <span class="break-all {entry.error || (entry.status >= 400) ? 'text-rose-400' : 'text-sky-300'}">{networkTitle(entry)}</span>
                  </summary>
                  <pre class="max-h-72 overflow-auto border-t border-slate-800 p-3 text-[11px] leading-relaxed text-slate-300">{JSON.stringify(entry, null, 2)}</pre>
                </details>
              {/each}
            {:else}
              <p class="p-6 text-center text-sm text-slate-500">No se capturaron requests.</p>
            {/if}
          </div>
        {/if}
      </aside>
    </div>
  {:else}
    <header>
      <h1 class="text-2xl font-bold">Grabaciones</h1>
      <p class="mt-1 text-sm text-slate-500">Videos de reproducción, consola y actividad de red.</p>
    </header>

    <section class="rounded-xl border border-border-light bg-surface-light p-5 dark:border-border-dark dark:bg-surface-dark">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="font-semibold">Extensión del navegador</h2>
          <p class="mt-1 text-sm text-slate-500">Crea un token y pégalo en la extensión cargada manualmente.</p>
        </div>
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50" onclick={createExtensionToken} disabled={tokenBusy}>
          {tokenBusy ? "Creando..." : "Conectar extensión"}
        </button>
      </div>
      {#if newToken}
        <div class="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
          <p class="text-sm font-medium">Copia este token ahora; no se volverá a mostrar.</p>
          <code class="mt-2 block break-all text-xs">{newToken}</code>
          <button class="mt-2 text-sm underline" onclick={() => navigator.clipboard?.writeText(newToken)}>Copiar token</button>
        </div>
      {/if}
      {#if tokens.length}
        <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
          {#each tokens as token (token.id)}
            <li class="flex items-center justify-between gap-3 py-2 text-sm">
              <span>{token.label || "Extensión"} · {token.revoked_at ? "Revocado" : "Activo"}</span>
              {#if !token.revoked_at}
                <button class="text-red-600 hover:underline" onclick={() => revokeToken(token)}>Revocar</button>
              {/if}
            </li>
          {/each}
        </ul>
      {/if}
    </section>

    <section class="rounded-xl border border-border-light bg-surface-light p-4 dark:border-border-dark dark:bg-surface-dark">
      <h2 class="mb-3 font-semibold">Todas las grabaciones</h2>
      {#if recordings.length}
        <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          {#each recordings as recording (recording.id)}
            <li class="rounded-lg border border-border-light p-3 dark:border-border-dark">
              <button class="block w-full text-left" onclick={() => openRecording(recording)}>
                <span class="block truncate font-medium">{recording.title || `Grabación #${recording.id}`}</span>
                <span class="mt-1 block text-xs text-slate-500">
                  {recording.recordable?.title || (recording.recordable_id ? `Bug #${recording.recordable_id}` : "Sin bug")}
                  · {recording.created_at ? new Date(recording.created_at).toLocaleString() : ""}
                </span>
              </button>
              <button class="mt-2 text-xs text-red-600 hover:underline" onclick={() => removeRecording(recording)}>Eliminar</button>
            </li>
          {/each}
        </ul>
      {:else}
        <p class="text-sm text-slate-500">Todavía no hay grabaciones.</p>
      {/if}
    </section>
  {/if}
</div>
