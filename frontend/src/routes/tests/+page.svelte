<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { Plus, FlaskConical, ChevronRight, Search } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getTestCases } from "$lib/api/test-cases.js";
  import { getAllComponents } from "$lib/api/components.js";
  import Pagination from "$lib/components/ui/Pagination.svelte";

  let testCases = $state([]);
  let components = $state([]);
  let loading = $state(true);
  let error = $state("");
  let pagination = $state({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });
  let searchTerm = $state("");
  let selectedComponent = $state("all");
  let selectedStatus = $state("all");
  let searchTimer;

  const statusMap = {
    untested: { label: "Sin probar", cls: "status-todo" },
    passed: { label: "Aprobado", cls: "status-done" },
    failed: { label: "Fallido", cls: "status-blocked" },
    blocked: { label: "Bloqueado", cls: "status-progress" },
  };

  async function load(page = 1, recoverEmptyPage = true) {
    loading = true;
    error = "";
    try {
      const response = await getTestCases({
        page,
        per_page: pagination.per_page,
        search: searchTerm.trim() || undefined,
        component_id:
          selectedComponent === "all" ? undefined : selectedComponent,
        status: selectedStatus === "all" ? undefined : selectedStatus,
      });
      const rows = response?.data ?? response ?? [];

      if (rows.length === 0 && page > 1 && recoverEmptyPage) {
        const previousPage = response?.last_page
          ? Math.min(page - 1, response.last_page)
          : page - 1;
        return await load(Math.max(1, previousPage), false);
      }

      testCases = rows;
      pagination = {
        current_page: Number(response?.current_page ?? page),
        last_page: Number(response?.last_page ?? 1),
        per_page: Number(response?.per_page ?? pagination.per_page),
        total: Number(response?.total ?? rows.length),
      };
    } catch (e) {
      error =
        e?.response?.data?.error ?? e?.message ?? "Error cargando test cases";
    } finally {
      loading = false;
    }
  }

  function scheduleSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => load(1), 300);
  }

  onMount(async () => {
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      components = await getAllComponents();
      await load(1);
    } catch (e) {
      error =
        e?.response?.data?.error ?? e?.message ?? "Error cargando test cases";
      loading = false;
    }
  });
</script>

<div class="p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1
        class="text-2xl font-bold text-text-main-light dark:text-text-main-dark"
      >
        Test Cases
      </h1>
      <p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
        Todos los test cases del sistema
      </p>
    </div>
    <a
      href="/tests/new"
      class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
    >
      <Plus size={18} />
      <span>Nuevo Test Case</span>
    </a>
  </div>

  <div class="grid gap-3 md:grid-cols-3">
    <div class="relative">
      <Search
        size={18}
        class="absolute left-3 top-1/2 -translate-y-1/2 text-text-sec-light dark:text-text-sec-dark"
      />
      <input
        type="text"
        bind:value={searchTerm}
        oninput={scheduleSearch}
        placeholder="Buscar test cases..."
        class="search-input"
        style="padding-left: 2.5rem"
      />
    </div>
    <select
      bind:value={selectedComponent}
      onchange={() => load(1)}
      class="form-input"
    >
      <option value="all">Todos los componentes</option>
      {#each components as component}
        <option value={component.id}>{component.name}</option>
      {/each}
    </select>
    <select
      bind:value={selectedStatus}
      onchange={() => load(1)}
      class="form-input"
    >
      <option value="all">Todos los estados</option>
      {#each Object.entries(statusMap) as [value, meta]}
        <option {value}>{meta.label}</option>
      {/each}
    </select>
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
      Cargando test cases...
    </p>
  {:else if error}
    <div
      class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200"
    >
      {error}
    </div>
  {:else}
    <div class="grid gap-3">
      {#each testCases as testCase}
        {@const sm = statusMap[testCase.status] ?? {
          label: testCase.status || "Sin probar",
          cls: "status-todo",
        }}
        <a
          href="/tests/{testCase.id}"
          class="group flex items-center justify-between p-4 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl hover:border-indigo-500/50 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200"
        >
          <div class="flex items-center gap-4">
            <div
              class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-text-sec-light dark:text-text-sec-dark group-hover:text-indigo-500 group-hover:bg-indigo-500/10 transition-colors"
            >
              <span class="font-mono font-bold text-sm">#{testCase.id}</span>
            </div>
            <div>
              <h3
                class="font-medium text-text-main-light dark:text-text-main-dark group-hover:underline"
              >
                {testCase.title}
              </h3>
              <div
                class="flex items-center gap-2 text-sm text-text-sec-light dark:text-text-sec-dark"
              >
                {#if testCase.component?.name}
                  <span
                    class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs"
                    >{testCase.component.name}</span
                  >
                {/if}
                <span class="line-clamp-1"
                  >{testCase.description || "Sin descripción"}</span
                >
              </div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="status-badge {sm.cls}">{sm.label}</span>
            <div
              class="text-text-sec-light dark:text-text-sec-dark group-hover:text-indigo-500 transition-colors"
            >
              <ChevronRight size={20} />
            </div>
          </div>
        </a>
      {:else}
        <div
          class="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-border-light dark:border-border-dark rounded-xl"
        >
          <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-full mb-3">
            <FlaskConical
              size={24}
              class="text-text-sec-light dark:text-text-sec-dark"
            />
          </div>
          <h3
            class="text-lg font-medium text-text-main-light dark:text-text-main-dark"
          >
            No se encontraron test cases
          </h3>
          <p class="text-text-sec-light dark:text-text-sec-dark mt-1">
            Ajustá la búsqueda o creá un nuevo test case.
          </p>
        </div>
      {/each}
    </div>
    <Pagination
      currentPage={pagination.current_page}
      lastPage={pagination.last_page}
      perPage={pagination.per_page}
      total={pagination.total}
      onPageChange={(page) => load(page)}
      onPerPageChange={(perPage) => {
        pagination = { ...pagination, per_page: perPage };
        load(1);
      }}
    />
  {/if}
</div>
