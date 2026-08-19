<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { Plus, Search, Network, BarChart3, Folder, Pencil, Trash2, Eye } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getComponents, deleteComponent } from "$lib/api/components.js";

  let components = $state([]);
  let loading = $state(true);
  let error = $state("");

  let filters = $state({ name: "", type: "", parent_id: "", criticality: "" });

  let knownTypes = $derived(
    Array.from(new Set(components.map((c) => c.type).filter(Boolean))).sort(),
  );

  let filtered = $derived(
    components.filter((c) => {
      const matchesName = !filters.name || c.name.toLowerCase().includes(filters.name.toLowerCase());
      const matchesType = !filters.type || c.type === filters.type;
      const matchesParent =
        filters.parent_id === "" || String(c.parent_id) === String(filters.parent_id);
      const matchesCritical =
        filters.criticality === "" ||
        (filters.criticality === "critical"
          ? (c.critical_dependency_count ?? 0) > 0
          : (c.critical_dependency_count ?? 0) === 0);
      return matchesName && matchesType && matchesParent && matchesCritical;
    }),
  );

  async function load() {
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
  }

  async function handleDelete(component) {
    if (!confirm(`¿Eliminar el componente "${component.name}"?`)) return;
    try {
      await deleteComponent(component.id);
      components = components.filter((c) => c.id !== component.id);
    } catch (e) {
      alert(e?.response?.data?.error ?? e?.message ?? "Error al eliminar");
    }
  }

  function applyFilters() {
    // filters are reactive via $derived; this is a no-op to keep the button semantic
  }

  onMount(load);
</script>

<div class="p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Componentes</h1>
      <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
        Gestioná los componentes del sistema, sus dependencias y test cases
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a
        href="/components/impact"
        class="flex items-center gap-2 px-3 py-2 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-main-light dark:text-text-main-dark hover:border-indigo-500/50 rounded-lg font-medium transition-colors"
      >
        <Network size={18} />
        <span>Visualizador</span>
      </a>
      <a
        href="/components/explorer"
        class="flex items-center gap-2 px-3 py-2 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-main-light dark:text-text-main-dark hover:border-indigo-500/50 rounded-lg font-medium transition-colors"
      >
        <BarChart3 size={18} />
        <span>Explorador</span>
      </a>
      <a
        href="/components/new"
        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
      >
        <Plus size={18} />
        <span>Nuevo Componente</span>
      </a>
    </div>
  </div>

  <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-4">
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[200px]">
        <Search
          size={18}
          class="absolute left-3 top-1/2 -translate-y-1/2 text-text-sec-light dark:text-text-sec-dark pointer-events-none"
        />
        <input
          type="text"
          bind:value={filters.name}
          placeholder="Buscar por nombre..."
          class="search-input"
          style="padding-left: 2.5rem"
        />
      </div>
      <select bind:value={filters.type} class="form-input w-auto">
        <option value="">Todos los tipos</option>
        {#each knownTypes as type}
          <option value={type}>{type}</option>
        {/each}
      </select>
      <select bind:value={filters.criticality} class="form-input w-auto">
        <option value="">Toda criticidad</option>
        <option value="critical">Con críticas</option>
        <option value="optional">Sin críticas</option>
      </select>
      <input
        type="number"
        placeholder="Parent ID"
        bind:value={filters.parent_id}
        class="form-input w-32"
      />
      <button type="button" onclick={applyFilters} class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
        Buscar
      </button>
    </div>
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando componentes...</p>
  {:else if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {:else}
    <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-text-sec-light dark:text-text-sec-dark border-b border-border-light dark:border-border-dark bg-slate-50 dark:bg-slate-900/50">
              <th class="px-4 py-3 font-medium">ID</th>
              <th class="px-4 py-3 font-medium">Nombre</th>
              <th class="px-4 py-3 font-medium">Tipo</th>
              <th class="px-4 py-3 font-medium">Padre</th>
              <th class="px-4 py-3 font-medium text-center">Deps</th>
              <th class="px-4 py-3 font-medium text-center">Críticas</th>
              <th class="px-4 py-3 font-medium text-center">Test Cases</th>
              <th class="px-4 py-3 font-medium text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {#each filtered as component}
              <tr class="border-b border-border-light dark:border-border-dark hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                <td class="px-4 py-3 text-text-sec-light dark:text-text-sec-dark">{component.id}</td>
                <td class="px-4 py-3">
                  <a href="/components/{component.id}" class="flex items-center gap-2 font-semibold text-text-main-light dark:text-text-main-dark hover:text-indigo-500 group">
                    <Folder size={16} class="text-indigo-500" />
                    <span class="group-hover:underline">{component.name}</span>
                  </a>
                </td>
                <td class="px-4 py-3">
                  <span class="status-badge status-todo">{component.type || "—"}</span>
                </td>
                <td class="px-4 py-3 text-text-sec-light dark:text-text-sec-dark">
                  {component.parent_name || "-"}
                </td>
                <td class="px-4 py-3 text-center text-text-main-light dark:text-text-main-dark">{component.dependency_count ?? 0}</td>
                <td class="px-4 py-3 text-center">
                  {#if (component.critical_dependency_count ?? 0) > 0}
                    <span class="status-badge status-blocked">{component.critical_dependency_count}</span>
                  {:else}
                    <span class="text-text-sec-light dark:text-text-sec-dark">0</span>
                  {/if}
                </td>
                <td class="px-4 py-3 text-center text-text-sec-light dark:text-text-sec-dark">{component.test_cases_count ?? 0}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <a
                      href="/components/{component.id}"
                      class="p-1.5 rounded-lg text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500 hover:bg-indigo-500/10 transition-colors"
                      title="Ver / Editar (incluye test cases y dependencias)"
                    >
                      <Eye size={18} />
                    </a>
                    <button
                      type="button"
                      onclick={() => handleDelete(component)}
                      class="p-1.5 rounded-lg text-text-sec-light dark:text-text-sec-dark hover:text-red-500 hover:bg-red-500/10 transition-colors"
                      title="Eliminar"
                    >
                      <Trash2 size={18} />
                    </button>
                  </div>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="8" class="px-4 py-12 text-center">
                  <div class="flex flex-col items-center justify-center text-center">
                    <Folder size={28} class="text-text-sec-light dark:text-text-sec-dark mb-3" />
                    <p class="text-text-main-light dark:text-text-main-dark font-medium">No hay componentes</p>
                    <p class="text-sm text-text-sec-light dark:text-text-sec-dark mt-1">
                      Empezá creando tu primer componente para organizar tus test cases.
                    </p>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </div>
  {/if}
</div>
