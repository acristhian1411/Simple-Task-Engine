<script>
  import { History, X, Loader2 } from "lucide-svelte";
  import { auditSidebar, closeAuditSidebar } from "$lib/stores/auditSidebar.svelte.js";
  import { getAuditsFor } from "$lib/api/audits.js";

  let audits = $state([]);
  let loading = $state(false);
  let error = $state("");

  const eventColors = {
    "Creación": "bg-green-500/10 text-green-600 dark:text-green-300",
    "Actualización": "bg-amber-500/10 text-amber-600 dark:text-amber-300",
    "Eliminación": "bg-red-500/10 text-red-600 dark:text-red-300",
  };

  function eventClass(evento) {
    return (
      eventColors[evento] ??
      "bg-slate-500/10 text-slate-600 dark:text-slate-300"
    );
  }

  async function fetchAudits(id, type) {
    loading = true;
    error = "";
    audits = [];
    try {
      const res = await getAuditsFor(id, type);
      audits = Array.isArray(res) ? res : res?.data ?? [];
    } catch (e) {
      error =
        e?.response?.data?.error ??
        e?.message ??
        "No se pudo cargar la auditoría.";
    } finally {
      loading = false;
    }
  }

  $effect(() => {
    if (auditSidebar.open && auditSidebar.auditableId) {
      fetchAudits(auditSidebar.auditableId, auditSidebar.auditableType);
    }
  });

  function handleKeydown(e) {
    if (e.key === "Escape") closeAuditSidebar();
  }
</script>

<svelte:window onkeydown={handleKeydown} />

{#if auditSidebar.open}
  <div
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[60]"
    onclick={closeAuditSidebar}
    role="presentation"
  ></div>

  <aside
    class="fixed top-0 right-0 h-full w-[22rem] max-w-full flex flex-col bg-surface-light dark:bg-surface-dark border-l border-border-light dark:border-border-dark z-[61]"
  >
    <div
      class="flex items-center justify-between px-5 py-4 border-b border-border-light dark:border-border-dark"
    >
      <div class="flex items-center gap-2">
        <History size={16} class="text-text-sec-light dark:text-text-sec-dark" />
        <h2 class="text-base font-bold text-text-main-light dark:text-text-main-dark">
          Historial de Auditoría
        </h2>
      </div>
      <button
        type="button"
        onclick={closeAuditSidebar}
        aria-label="Cerrar auditoría"
        class="p-1.5 text-text-sec-light dark:text-text-sec-dark hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-text-main-light dark:hover:text-text-main-dark rounded-lg transition-colors"
      >
        <X size={18} />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-3 text-text-sec-light dark:text-text-sec-dark">
      {#if loading}
        <div class="flex flex-col items-center gap-2 mt-8 text-text-sec-light dark:text-text-sec-dark">
          <Loader2 size={28} class="animate-spin" />
          <p class="text-sm">Cargando auditoría...</p>
        </div>
      {:else if error}
        <p class="text-center text-sm text-red-500 mt-8">{error}</p>
      {:else if audits.length === 0}
        <p class="text-center text-sm mt-8">
          No hay registros de auditoría para este elemento.
        </p>
      {:else}
        {#each audits as audit (audit.id)}
          <div
            class="border border-border-light dark:border-border-dark rounded-xl p-3 flex flex-col gap-1.5 bg-slate-50 dark:bg-slate-900/40"
          >
            <div class="flex items-center justify-between gap-2">
              <span
                class="text-xs font-semibold px-2 py-0.5 rounded-full {eventClass(audit.evento)}"
              >
                {audit.evento}
              </span>
              <span class="text-xs text-text-sec-light dark:text-text-sec-dark shrink-0">
                {audit.fecha}
              </span>
            </div>
            {#if audit.usuario_id}
              <p class="text-xs text-text-main-light dark:text-text-main-dark">
                {audit.usuario ?? `Usuario #${audit.usuario_id}`}
              </p>
            {/if}
            {#if audit.cambios?.length}
              <ul class="pl-5 list-disc space-y-0.5">
                {#each audit.cambios as cambio, i (i)}
                  <li class="text-xs text-text-sec-light dark:text-text-sec-dark leading-5">
                    {cambio}
                  </li>
                {/each}
              </ul>
            {/if}
            {#if audit.ip}
              <p class="text-xs text-text-sec-light dark:text-text-sec-dark opacity-70">IP: {audit.ip}</p>
            {/if}
          </div>
        {/each}
      {/if}
    </div>
  </aside>
{/if}
