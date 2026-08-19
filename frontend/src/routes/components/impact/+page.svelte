<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Network, List as ListIcon } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getComponents, getComponentImpact, getComponentDependents, getComponentDependencies } from "$lib/api/components.js";
  import ImpactGraph from "$lib/components/flow/ImpactGraph.svelte";

  let components = $state([]);
  let loading = $state(true);
  let error = $state("");
  let selectedId = $state("");
  let mode = $state("impact");
  let view = $state("graph");
  let criticalityFilter = $state("all");
  let rows = $state([]);

  async function loadRows() {
    if (!selectedId) return;
    error = "";
    try {
      if (mode === "impact") {
        const result = await getComponentImpact(selectedId);
        rows = (Array.isArray(result) ? result : result?.data ?? []).map((r) => ({ ...r, source: "impact" }));
        return;
      }
      if (mode === "dependents") {
        const result = await getComponentDependents(selectedId);
        rows = (Array.isArray(result) ? result : result?.data ?? []).map((r) => ({ ...r, source: "direct-dependents" }));
        return;
      }
      const result = await getComponentDependencies(selectedId);
      rows = (Array.isArray(result) ? result : result?.data ?? []).map((r) => ({ ...r, source: "direct-dependencies" }));
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando datos";
    }
  }

  let filteredRows = $derived(
    criticalityFilter === "all" ? rows : rows.filter((r) => r.criticality === criticalityFilter),
  );

  onMount(async () => {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const res = await getComponents({ per_page: 100 });
      components = res?.data ?? res ?? [];
      if (components.length > 0) {
        selectedId = String(components[0].id);
      }
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando componentes";
    } finally {
      loading = false;
    }
  });

  $effect(() => {
    if (selectedId) loadRows();
  });
</script>

<div class="p-6 space-y-6">
  <div class="flex items-center gap-4">
    <a href="/components" class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
      <ArrowLeft size={20} />
    </a>
    <div>
      <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Visualizador de Impacto</h1>
      <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
        Explorá el impacto de un componente sobre el resto del sistema
      </p>
    </div>
  </div>

  {#if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {/if}

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else}
    <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-4">
      <div class="flex flex-wrap items-center gap-3">
        <select bind:value={selectedId} class="form-input w-auto min-w-[240px]">
          {#each components as comp}
            <option value={String(comp.id)}>{comp.name} (#{comp.id})</option>
          {/each}
        </select>

        <div class="flex rounded-lg border border-border-light dark:border-border-dark overflow-hidden">
          <button
            type="button"
            onclick={() => (mode = "impact")}
            class={`px-3 py-2 text-sm font-medium transition-colors ${mode === "impact" ? "bg-indigo-600 text-white" : "bg-transparent text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500"}`}
          >
            Impacto recursivo
          </button>
          <button
            type="button"
            onclick={() => (mode = "dependents")}
            class={`px-3 py-2 text-sm font-medium transition-colors ${mode === "dependents" ? "bg-indigo-600 text-white" : "bg-transparent text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500"}`}
          >
            Dependientes directos
          </button>
          <button
            type="button"
            onclick={() => (mode = "dependencies")}
            class={`px-3 py-2 text-sm font-medium transition-colors ${mode === "dependencies" ? "bg-indigo-600 text-white" : "bg-transparent text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500"}`}
          >
            Dependencias directas
          </button>
        </div>

        <select bind:value={criticalityFilter} class="form-input w-auto">
          <option value="all">all</option>
          <option value="critical">critical</option>
          <option value="optional">optional</option>
        </select>

        <div class="flex rounded-lg border border-border-light dark:border-border-dark overflow-hidden ml-auto">
          <button
            type="button"
            onclick={() => (view = "graph")}
            title="Grafo"
            class={`px-3 py-2 transition-colors ${view === "graph" ? "bg-indigo-600 text-white" : "bg-transparent text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500"}`}
          >
            <Network size={18} />
          </button>
          <button
            type="button"
            onclick={() => (view = "list")}
            title="Lista"
            class={`px-3 py-2 transition-colors ${view === "list" ? "bg-indigo-600 text-white" : "bg-transparent text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500"}`}
          >
            <ListIcon size={18} />
          </button>
        </div>
      </div>
    </div>

    <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6">
      <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark mb-4">Resultado</h2>

      {#if view === "graph" && selectedId}
        <ImpactGraph
          rootId={Number(selectedId)}
          rootName={components.find((c) => String(c.id) === selectedId)?.name ?? `#${selectedId}`}
          rows={filteredRows}
          {mode}
        />
      {:else}
        <div class="space-y-2">
          {#each filteredRows as row}
            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 border border-border-light dark:border-border-dark rounded-lg">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-text-main-light dark:text-text-main-dark">{row.name}</span>
                {#if row.type}
                  <span class="status-badge status-todo">{row.type}</span>
                {/if}
                {#if row.criticality}
                  <span class="status-badge {row.criticality === 'critical' ? 'status-blocked' : 'status-todo'}">{row.criticality}</span>
                {/if}
              </div>
              {#if typeof row.depth === "number"}
                <span class="status-badge status-progress">depth {row.depth}</span>
              {/if}
            </div>
          {:else}
            <p class="text-text-sec-light dark:text-text-sec-dark text-sm">No hay resultados para el modo seleccionado.</p>
          {/each}
        </div>
      {/if}
    </div>
  {/if}
</div>
