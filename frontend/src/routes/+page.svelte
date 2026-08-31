<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import {
    BookOpen,
    Bug,
    FlaskConical,
    AlertTriangle,
    Clock,
  } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getDashboard } from "$lib/api/dashboard.js";

  let stats = $state({
    totalModules: 0,
    totalTestCases: 0,
    totalBugs: 0,
    openBugs: 0,
  });
  let bugsBySeverity = $state({});
  let bugsByStatus = $state({});
  let testCasesByStatus = $state({});
  let recentBugs = $state([]);
  let modules = $state([]);
  let loading = $state(true);
  let error = $state("");

  const severityStyles = {
    critical: "bg-red-500/10 text-red-300",
    high: "bg-orange-500/10 text-orange-300",
    medium: "bg-amber-500/10 text-amber-300",
    low: "bg-emerald-500/10 text-emerald-300",
  };

  const bugStatusStyles = {
    open: "status-todo",
    in_progress: "status-progress",
    resolved: "status-done",
    closed: "status-blocked",
  };

  const testCaseStatusStyles = {
    untested: "status-todo",
    passed: "status-done",
    failed: "status-blocked",
    blocked: "status-progress",
  };

  const statusLabels = {
    open: "Abierto",
    in_progress: "En progreso",
    resolved: "Resuelto",
    closed: "Cerrado",
    untested: "Sin probar",
    passed: "Aprobado",
    failed: "Fallido",
    blocked: "Bloqueado",
  };

  /** @param {string} value @param {Record<string,string>} map */
  function styleFor(value, map) {
    return map[value] ?? "status-todo";
  }

  /** @param {string} value */
  function label(value) {
    return statusLabels[value] ?? value.replace(/_/g, " ");
  }

  /** @param {string | Date} date */
  function formatDate(date) {
    return new Date(date).toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  }

  const statCards = [
    {
      label: "Componentes",
      value: () => stats.totalModules,
      icon: BookOpen,
      accent: "from-indigo-500 to-indigo-400",
    },
    {
      label: "Casos de prueba",
      value: () => stats.totalTestCases,
      icon: FlaskConical,
      accent: "from-cyan-500 to-cyan-400",
    },
    {
      label: "Bugs totales",
      value: () => stats.totalBugs,
      icon: Bug,
      accent: "from-purple-500 to-purple-400",
    },
    {
      label: "Bugs abiertos",
      value: () => stats.openBugs,
      icon: AlertTriangle,
      accent: "from-rose-500 to-rose-400",
    },
  ];

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const res = await getDashboard();
      const data = res?.data ?? res;
      stats = data?.stats ?? stats;
      bugsBySeverity = data?.bugsBySeverity ?? {};
      bugsByStatus = data?.bugsByStatus ?? {};
      testCasesByStatus = data?.testCasesByStatus ?? {};
      recentBugs = data?.recentBugs ?? [];
      modules = data?.modules ?? [];
    } catch (e) {
      error =
        e?.response?.data?.error ?? e?.message ?? "Error cargando el dashboard";
    } finally {
      loading = false;
    }
  }

  onMount(load);
</script>

<div class="p-6 space-y-8">
  <!-- Header -->
  <div>
    <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">
      Dashboard
    </h1>
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark mt-1">
      Resumen general de tu registro de testing manual
    </p>
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
      Cargando dashboard...
    </p>
  {:else if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">
      {error}
    </div>
  {:else}
    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {#each statCards as card}
        <div
          class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-5 hover:border-indigo-500/50 transition-colors"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-text-sec-light dark:text-text-sec-dark uppercase tracking-wide">
              {card.label}
            </span>
            <div
              class="w-9 h-9 rounded-lg bg-gradient-to-tr {card.accent} flex items-center justify-center shadow-lg"
            >
              <card.icon size={18} class="text-white" />
            </div>
          </div>
          <p class="text-3xl font-bold text-text-main-light dark:text-text-main-dark mt-3">
            {card.value()}
          </p>
        </div>
      {/each}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Bugs por severidad -->
      <div
        class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-6"
      >
        <h2 class="text-sm font-semibold text-text-main-light dark:text-text-main-dark mb-4">
          Bugs por severidad
        </h2>
        {#if Object.keys(bugsBySeverity).length === 0}
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
            Todavía no hay bugs registrados.
          </p>
        {:else}
          <div class="space-y-3">
            {#each Object.entries(bugsBySeverity) as [severity, count]}
              {@const pct = Math.round((count / stats.totalBugs) * 100)}
              <div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span
                    class="status-badge capitalize {styleFor(severity, severityStyles)}"
                  >
                    {label(severity)}
                  </span>
                  <span class="text-text-sec-light dark:text-text-sec-dark">{count}</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400"
                    style="width: {pct}%"
                  ></div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Bugs por estado -->
      <div
        class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-6"
      >
        <h2 class="text-sm font-semibold text-text-main-light dark:text-text-main-dark mb-4">
          Bugs por estado
        </h2>
        {#if Object.keys(bugsByStatus).length === 0}
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
            Todavía no hay bugs registrados.
          </p>
        {:else}
          <div class="flex flex-wrap gap-2">
            {#each Object.entries(bugsByStatus) as [status, count]}
              <span
                class="status-badge {styleFor(status, bugStatusStyles)}"
              >
                <span class="capitalize">{label(status)}</span>
                <span class="opacity-70 ml-1">{count}</span>
              </span>
            {/each}
          </div>
        {/if}

        <h2 class="text-sm font-semibold text-text-main-light dark:text-text-main-dark mt-6 mb-4">
          Casos de prueba por estado
        </h2>
        {#if Object.keys(testCasesByStatus).length === 0}
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
            Todavía no hay casos de prueba.
          </p>
        {:else}
          <div class="flex flex-wrap gap-2">
            {#each Object.entries(testCasesByStatus) as [status, count]}
              <span class="status-badge {styleFor(status, testCaseStatusStyles)}">
                <span class="capitalize">{label(status)}</span>
                <span class="opacity-70 ml-1">{count}</span>
              </span>
            {/each}
          </div>
        {/if}
      </div>
    </div>

    <!-- Bugs recientes -->
    <div
      class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark overflow-hidden"
    >
      <div
        class="px-6 py-4 border-b border-border-light dark:border-border-dark flex items-center justify-between"
      >
        <h2 class="text-sm font-semibold text-text-main-light dark:text-text-main-dark">
          Bugs recientes
        </h2>
        <a
          href="/bugs"
          class="text-xs text-indigo-500 hover:text-indigo-400 transition-colors"
          >Ver todos →</a
        >
      </div>

      {#if recentBugs.length === 0}
        <p class="text-sm text-text-sec-light dark:text-text-sec-dark px-6 py-8 text-center">
          No hay bugs registrados todavía.
        </p>
      {:else}
        <div class="divide-y divide-slate-200 dark:divide-slate-800">
          {#each recentBugs as bug}
            <div
              class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
            >
              <div class="min-w-0">
                <a
                  href="/bugs/{bug.id}"
                  class="text-sm font-medium text-text-main-light dark:text-text-main-dark truncate hover:text-indigo-500"
                >
                  {bug.title}
                </a>
                <p class="text-xs text-text-sec-light dark:text-text-sec-dark mt-0.5 flex items-center gap-2">
                  {#if bug.moduleName}
                    <span>{bug.moduleName}</span>
                    <span class="text-text-sec-light dark:text-text-sec-dark">•</span>
                  {/if}
                  <span class="flex items-center gap-1">
                    <Clock size={12} />
                    {formatDate(bug.createdAt)}
                  </span>
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="status-badge capitalize {styleFor(bug.severity, severityStyles)}">
                  {label(bug.severity)}
                </span>
                <span class="status-badge capitalize {styleFor(bug.status, bugStatusStyles)}">
                  {label(bug.status)}
                </span>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>

    <!-- Componentes -->
    <div
      class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-6"
    >
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-text-main-light dark:text-text-main-dark">
          Componentes
        </h2>
        <a
          href="/components"
          class="text-xs text-indigo-500 hover:text-indigo-400 transition-colors"
          >Ver todos →</a
        >
      </div>
      {#if modules.length === 0}
        <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
          Todavía no hay componentes creados.
        </p>
      {:else}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          {#each modules as mod}
            <a
              href="/components/{mod.id}"
              class="rounded-lg border border-border-light dark:border-border-dark bg-slate-50 dark:bg-slate-800/40 p-3 hover:border-indigo-500/50 transition-colors"
            >
              <p class="text-sm font-medium text-text-main-light dark:text-text-main-dark truncate">
                {mod.name}
              </p>
              {#if mod.description}
                <p class="text-xs text-text-sec-light dark:text-text-sec-dark truncate mt-1">
                  {mod.description}
                </p>
              {/if}
            </a>
          {/each}
        </div>
      {/if}
    </div>
  {/if}
</div>
