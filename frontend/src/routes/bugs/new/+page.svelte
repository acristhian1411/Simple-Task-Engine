<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Save } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { createBug } from "$lib/api/bugs.js";
  import { getComponents } from "$lib/api/components.js";
  import { getTestCases } from "$lib/api/test-cases.js";

  let components = $state([]);
  let testCases = $state([]);
  let loading = $state(true);
  let error = $state("");
  let submitting = $state(false);

  let form = $state({
    title: "",
    description: "",
    severity: "medium",
    status: "open",
    test_case_id: "",
  });

  let selectedComponentId = $state("");

  let filteredTestCases = $derived(
    testCases.filter((tc) => {
      if (!selectedComponentId) return true;
      return String(tc.component_id) === String(selectedComponentId);
    }),
  );

  onMount(async () => {
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const params = new URLSearchParams(window.location.search);
      const [compRes, tcRes] = await Promise.all([
        getComponents({ per_page: 100 }),
        getTestCases({ per_page: 100 }),
      ]);
      components = compRes?.data ?? compRes ?? [];
      testCases = tcRes?.data ?? tcRes ?? [];

      const tcId = params.get("testCaseId");
      if (tcId) form.test_case_id = tcId;
      const compId = params.get("componentId");
      if (compId) selectedComponentId = compId;
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando datos";
    } finally {
      loading = false;
    }
  });

  async function handleSubmit() {
    if (!form.title || !form.description) {
      error = "Título y descripción son obligatorios";
      return;
    }
    submitting = true;
    error = "";
    try {
      const payload = {
        title: form.title,
        description: form.description,
        severity: form.severity,
        status: form.status,
        test_case_id: form.test_case_id || null,
      };
      await createBug(payload);
      goto("/bugs");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al guardar";
    } finally {
      submitting = false;
    }
  }
</script>

<div class="max-w-3xl mx-auto p-6 space-y-6">
  <div class="flex items-center gap-4">
    <a
      href="/bugs"
      class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
    >
      <ArrowLeft size={20} />
    </a>
    <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">
      Reportar un Bug
    </h1>
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else if error && components.length === 0}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {:else}
    <form
      onsubmit={(e) => {
        e.preventDefault();
        handleSubmit();
      }}
      class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6"
    >
      <div class="grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
          <label for="title" class="block text-sm font-medium">Título</label>
          <input id="title" type="text" required placeholder="ej. El botón de login no responde" bind:value={form.title} class="form-input" />
        </div>
        <div class="space-y-2">
          <label for="severity" class="block text-sm font-medium">Severidad</label>
          <select id="severity" bind:value={form.severity} class="form-input">
            <option value="low">Baja</option>
            <option value="medium">Media</option>
            <option value="high">Alta</option>
            <option value="critical">Crítica</option>
          </select>
        </div>
      </div>

      <div class="space-y-2">
        <label for="description" class="block text-sm font-medium">Descripción</label>
        <textarea id="description" rows="4" required placeholder="Describí el comportamiento inesperado y los pasos para reproducirlo" bind:value={form.description} class="form-input resize-none"></textarea>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
          <label for="componentId" class="block text-sm font-medium">Componente</label>
          <select id="componentId" bind:value={selectedComponentId} class="form-input">
            <option value="">Todos los componentes</option>
            {#each components as component}
              <option value={component.id}>{component.name}</option>
            {/each}
          </select>
        </div>
        <div class="space-y-2">
          <label for="test_case_id" class="block text-sm font-medium">Test Case relacionado</label>
          <select id="test_case_id" bind:value={form.test_case_id} class="form-input">
            <option value="">Sin caso relacionado</option>
            {#each filteredTestCases as testCase}
              <option value={testCase.id}>{testCase.title}</option>
            {/each}
          </select>
        </div>
      </div>

      <div class="space-y-2">
        <label for="status" class="block text-sm font-medium">Estado</label>
        <select id="status" bind:value={form.status} class="form-input">
          <option value="open">Abierto</option>
          <option value="in_progress">En progreso</option>
          <option value="resolved">Resuelto</option>
          <option value="closed">Cerrado</option>
        </select>
      </div>

      {#if error}
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
      {/if}

      <div class="flex justify-end">
        <button
          type="submit"
          disabled={submitting}
          class="flex items-center gap-2 px-8 py-3 bg-red-600 hover:bg-red-700 disabled:opacity-40 text-white rounded-xl font-medium transition-colors"
        >
          <Save size={20} />
          <span>{submitting ? "Guardando..." : "Guardar Bug"}</span>
        </button>
      </div>
    </form>
  {/if}
</div>
