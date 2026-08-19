<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { Plus, Folder, ChevronRight, Search } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getComponents } from "$lib/api/components.js";

  let components = $state([]);
  let loading = $state(true);
  let error = $state("");
  let searchTerm = $state("");

  let filtered = $derived(
    components.filter(
      (c) =>
        c.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (c.description || "")
          .toLowerCase()
          .includes(searchTerm.toLowerCase()),
    ),
  );

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
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Componentes</h1>
      <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
        Gestioná los componentes del sistema y sus test cases
      </p>
    </div>
    <a
      href="/components/new"
      class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
    >
      <Plus size={18} />
      <span>Nuevo Componente</span>
    </a>
  </div>

  <div class="relative max-w-md">
    <Search size={18} class="absolute left-3 top-1/2 -translate-y-1/2 text-text-sec-light dark:text-text-sec-dark" />
    <input
      type="text"
      bind:value={searchTerm}
      placeholder="Buscar componentes..."
      class="search-input"
      style="padding-left: 2.5rem"
    />
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando componentes...</p>
  {:else if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {#each filtered as component}
        <a
          href="/components/{component.id}"
          class="group relative p-5 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl hover:border-indigo-500/50 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-300"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-500 group-hover:text-indigo-400 group-hover:bg-indigo-500/20 transition-colors">
              <Folder size={24} />
            </div>
            {#if component.test_cases_count !== undefined}
              <span class="text-xs text-text-sec-light dark:text-text-sec-dark bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                {component.test_cases_count} test cases
              </span>
            {/if}
            <div class="text-text-sec-light dark:text-text-sec-dark group-hover:text-indigo-500 transition-colors">
              <ChevronRight size={20} />
            </div>
          </div>
          <h3 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark group-hover:underline mb-2">
            {component.name}
          </h3>
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark line-clamp-2">
            {component.description || "Sin descripción"}
          </p>
        </a>
      {:else}
        <div class="col-span-full flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-border-light dark:border-border-dark rounded-xl">
          <div class="p-4 bg-slate-100 dark:bg-slate-900 rounded-full mb-4">
            <Folder size={32} class="text-text-sec-light dark:text-text-sec-dark" />
          </div>
          <h3 class="text-lg font-medium text-text-main-light dark:text-text-main-dark">No hay componentes</h3>
          <p class="text-text-sec-light dark:text-text-sec-dark mt-1 max-w-sm">
            Empezá creando tu primer componente para organizar tus test cases.
          </p>
          <a href="/components/new" class="mt-4 text-indigo-500 hover:text-indigo-400 font-medium">
            Crear componente &rarr;
          </a>
        </div>
      {/each}
    </div>
  {/if}
</div>
