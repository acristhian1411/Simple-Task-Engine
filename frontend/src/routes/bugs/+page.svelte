<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { Bug, Plus, Search } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getBugs } from "$lib/api/bugs.js";
  import { getComponents } from "$lib/api/components.js";

  let bugs = $state([]);
  let components = $state([]);
  let loading = $state(true);
  let error = $state("");

  let searchTerm = $state("");
  let selectedStatus = $state("all");
  let selectedSeverity = $state("all");
  let selectedComponent = $state("all");

  const statusMap = {
    open: { label: "Abierto", cls: "status-todo" },
    in_progress: { label: "En progreso", cls: "status-progress" },
    resolved: { label: "Resuelto", cls: "status-done" },
    closed: { label: "Cerrado", cls: "status-blocked" },
  };

  const severityMap = {
    low: { label: "Baja", cls: "bg-emerald-500/10 text-emerald-300" },
    medium: { label: "Media", cls: "bg-amber-500/10 text-amber-300" },
    high: { label: "Alta", cls: "bg-orange-500/10 text-orange-300" },
    critical: { label: "Crítica", cls: "bg-red-500/10 text-red-300" },
  };

  let filteredBugs = $derived(
    bugs.filter((bug) => {
      const matchesSearch =
        bug.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (bug.description || "")
          .toLowerCase()
          .includes(searchTerm.toLowerCase());
      const matchesStatus =
        selectedStatus === "all" || bug.status === selectedStatus;
      const matchesSeverity =
        selectedSeverity === "all" || bug.severity === selectedSeverity;
      const bugComponentName =
        bug.test_case?.component?.name ?? bug.component_name ?? "";
      const matchesComponent =
        selectedComponent === "all" ||
        bugComponentName === selectedComponent;
      return matchesSearch && matchesStatus && matchesSeverity && matchesComponent;
    }),
  );

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const [bugsRes, compRes] = await Promise.all([
        getBugs(),
        getComponents({ per_page: 100 }),
      ]);
      bugs = bugsRes?.data ?? bugsRes ?? [];
      components = compRes?.data ?? compRes ?? [];
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando bugs";
    } finally {
      loading = false;
    }
  }

  async function handleDelete(bug) {
    if (!confirm(`¿Eliminar el bug "${bug.title}"?`)) return;
    try {
      const { deleteBug } = await import("$lib/api/bugs.js");
      await deleteBug(bug.id);
      bugs = bugs.filter((b) => b.id !== bug.id);
    } catch (e) {
      alert(e?.response?.data?.error ?? e?.message ?? "Error al eliminar");
    }
  }

  onMount(load);
</script>

<div class="p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">
        Bugs
      </h1>
      <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
        Gestioná los problemas detectados durante la ejecución de tests
      </p>
    </div>
    <a
      href="/bugs/new"
      class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
    >
      <Plus size={18} />
      <span>Nuevo Bug</span>
    </a>
  </div>

  <div class="grid gap-3 md:grid-cols-4">
    <div class="relative md:col-span-1">
      <Search
        size={18}
        class="absolute left-3 top-1/2 -translate-y-1/2 text-text-sec-light dark:text-text-sec-dark"
      />
      <input
        type="text"
        bind:value={searchTerm}
        placeholder="Buscar bugs..."
        class="search-input"
        style="padding-left: 2.5rem"
      />
    </div>
    <select
      bind:value={selectedStatus}
      class="form-input"
    >
      <option value="all">Todos los estados</option>
      {#each Object.entries(statusMap) as [value, meta]}
        <option value={value}>{meta.label}</option>
      {/each}
    </select>
    <select
      bind:value={selectedSeverity}
      class="form-input"
    >
      <option value="all">Todas las severidades</option>
      {#each Object.entries(severityMap) as [value, meta]}
        <option value={value}>{meta.label}</option>
      {/each}
    </select>
    <select
      bind:value={selectedComponent}
      class="form-input"
    >
      <option value="all">Todos los componentes</option>
      {#each components as component}
        <option value={component.name}>{component.name}</option>
      {/each}
    </select>
  </div>

  {#if loading}
    <div class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando bugs...</div>
  {:else if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">
      {error}
    </div>
  {:else}
    <div class="grid gap-3">
      {#each filteredBugs as bug}
        {@const sm = statusMap[bug.status] ?? { label: bug.status, cls: "status-todo" }}
        {@const vm = severityMap[bug.severity] ?? { label: bug.severity, cls: "bg-slate-500/10 text-slate-300" }}
        <div
          class="p-4 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl space-y-3"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <Bug size={16} class="text-red-400" />
                <h3 class="font-semibold text-text-main-light dark:text-text-main-dark">{bug.title}</h3>
              </div>
              <p class="text-sm text-text-sec-light dark:text-text-sec-dark">{bug.description}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <span class="status-badge {sm.cls}">{sm.label}</span>
              <span class="status-badge {vm.cls}">{vm.label}</span>
            </div>
          </div>
          <div
            class="flex flex-wrap items-center justify-between gap-3 text-sm text-text-sec-light dark:text-text-sec-dark"
          >
            <div class="flex flex-wrap items-center gap-3">
              {#if bug.test_case?.title}
                <span>Caso: {bug.test_case.title}</span>
              {/if}
              {#if bug.test_case?.component?.name}
                <span>Componente: {bug.test_case.component.name}</span>
              {/if}
              {#if bug.created_at}
                <span>Creado: {new Date(bug.created_at).toLocaleDateString()}</span>
              {/if}
            </div>
            <div class="flex items-center gap-2">
              <a
                href={`/bugs/${bug.id}`}
                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-text-main-light dark:text-text-main-dark hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                >Editar</a
              >
              <button
                type="button"
                onclick={() => handleDelete(bug)}
                class="px-3 py-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors"
                >Eliminar</button
              >
            </div>
          </div>
        </div>
      {:else}
        <div
          class="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-border-light dark:border-border-dark rounded-xl"
        >
          <Bug size={24} class="text-text-sec-light dark:text-text-sec-dark mb-3" />
          <p class="text-text-main-light dark:text-text-main-dark font-medium">No hay bugs</p>
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark mt-1">
            Reportá un nuevo bug para empezar a seguirlo.
          </p>
        </div>
      {/each}
    </div>
  {/if}
</div>
