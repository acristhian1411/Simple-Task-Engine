<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Folder, AlertTriangle, Link2 } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getComponents } from "$lib/api/components.js";

  let components = $state([]);
  let loading = $state(true);
  let error = $state("");

  let summary = $derived.by(() => {
    const total = components.length;
    const criticalEdges = components.reduce(
      (acc, row) => acc + Number(row.critical_dependency_count ?? 0),
      0,
    );
    const totalEdges = components.reduce(
      (acc, row) => acc + Number(row.dependency_count ?? 0),
      0,
    );
    const byType = components.reduce((acc, row) => {
      const key = row.type || "unknown";
      acc[key] = (acc[key] || 0) + 1;
      return acc;
    }, {});
    return { total, criticalEdges, totalEdges, byType };
  });

  onMount(async () => {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const res = await getComponents({ per_page: 100 });
      components = res?.data ?? res ?? [];
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando componentes";
    } finally {
      loading = false;
    }
  });
</script>

<div class="p-6 space-y-6">
  <div class="flex items-center gap-4">
    <a href="/components" class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
      <ArrowLeft size={20} />
    </a>
    <div>
      <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Explorador del Sistema</h1>
      <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
        Resumen del inventario de componentes y sus dependencias
      </p>
    </div>
  </div>

  {#if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {/if}

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6">
        <div class="flex items-center gap-2 text-text-sec-light dark:text-text-sec-dark text-sm">
          <Folder size={18} class="text-indigo-500" />
          Componentes totales
        </div>
        <p class="text-3xl font-bold text-text-main-light dark:text-text-main-dark mt-2">
          {summary.total}
        </p>
        <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-2">Inventario actual del sistema</p>
      </div>
      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6">
        <div class="flex items-center gap-2 text-text-sec-light dark:text-text-sec-dark text-sm">
          <AlertTriangle size={18} class="text-red-500" />
          Dependencias críticas
        </div>
        <p class="text-3xl font-bold text-text-main-light dark:text-text-main-dark mt-2">
          {summary.criticalEdges}
        </p>
        <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-2">Vínculos que bloquean borrado</p>
      </div>
      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6">
        <div class="flex items-center gap-2 text-text-sec-light dark:text-text-sec-dark text-sm">
          <Link2 size={18} class="text-indigo-500" />
          Vínculos de dependencia
        </div>
        <p class="text-3xl font-bold text-text-main-light dark:text-text-main-dark mt-2">
          {summary.totalEdges}
        </p>
        <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-2">Conexiones de dependencia activas</p>
      </div>
    </div>

    <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6">
      <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark mb-4">
        Distribución por Tipo
      </h2>
      <div class="flex flex-wrap gap-2">
        {#each Object.entries(summary.byType) as [type, count]}
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20">{type}: {count}</span>
        {:else}
          <p class="text-text-sec-light dark:text-text-sec-dark text-sm">No hay componentes registrados.</p>
        {/each}
      </div>
    </div>
  {/if}
</div>
