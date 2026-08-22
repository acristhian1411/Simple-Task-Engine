<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Save, Trash2 } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getBug, updateBug, deleteBug } from "$lib/api/bugs.js";
  import { getComponents } from "$lib/api/components.js";
  import { getTestCases } from "$lib/api/test-cases.js";
  import Comments from "$lib/components/Comments.svelte";

  let loading = $state(true);
  let error = $state("");
  let submitting = $state(false);

  let bug = $state(null);
  let components = $state([]);
  let testCases = $state([]);
  let selectedComponentId = $state("");

  let filteredTestCases = $derived(
    testCases.filter((tc) => {
      if (!selectedComponentId) return true;
      return String(tc.component_id) === String(selectedComponentId);
    }),
  );

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const id = window.location.pathname.split("/").filter(Boolean).pop();
      const [bugRes, compRes, tcRes] = await Promise.all([
        getBug(id),
        getComponents({ per_page: 100 }),
        getTestCases({ per_page: 100 }),
      ]);
      bug = bugRes?.data ?? bugRes;
      components = compRes?.data ?? compRes ?? [];
      testCases = tcRes?.data ?? tcRes ?? [];
      selectedComponentId = bug?.test_case?.component_id ?? "";
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando bug";
    } finally {
      loading = false;
    }
  }

  async function handleSubmit() {
    if (!bug.title || !bug.description) {
      error = "Título y descripción son obligatorios";
      return;
    }
    submitting = true;
    error = "";
    try {
      await updateBug(bug.id, {
        title: bug.title,
        description: bug.description,
        severity: bug.severity,
        status: bug.status,
        test_case_id: bug.test_case_id || null,
      });
      goto("/bugs");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al guardar";
    } finally {
      submitting = false;
    }
  }

  async function handleDelete() {
    if (!confirm(`¿Eliminar el bug "${bug.title}"?`)) return;
    try {
      await deleteBug(bug.id);
      goto("/bugs");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al eliminar";
    }
  }

  onMount(load);
</script>

<div class="max-w-3xl mx-auto p-6 space-y-6">
  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else if error && !bug}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {:else if bug}
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <a href="/bugs" class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
          <ArrowLeft size={20} />
        </a>
        <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Editar Bug</h1>
      </div>
      <button
        type="button"
        onclick={handleDelete}
        class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 rounded-lg font-medium transition-colors border border-red-500/20"
      >
        <Trash2 size={18} />
        <span>Eliminar</span>
      </button>
    </div>

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
          <input id="title" type="text" required bind:value={bug.title} class="form-input" />
        </div>
        <div class="space-y-2">
          <label for="severity" class="block text-sm font-medium">Severidad</label>
          <select id="severity" bind:value={bug.severity} class="form-input">
            <option value="low">Baja</option>
            <option value="medium">Media</option>
            <option value="high">Alta</option>
            <option value="critical">Crítica</option>
          </select>
        </div>
      </div>

      <div class="space-y-2">
        <label for="description" class="block text-sm font-medium">Descripción</label>
        <textarea id="description" rows="4" required bind:value={bug.description} class="form-input resize-none"></textarea>
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
          <select id="test_case_id" bind:value={bug.test_case_id} class="form-input">
            <option value="">Sin caso relacionado</option>
            {#each filteredTestCases as testCase}
              <option value={testCase.id}>{testCase.title}</option>
            {/each}
          </select>
        </div>
      </div>

      <div class="space-y-2">
        <label for="status" class="block text-sm font-medium">Estado</label>
        <select id="status" bind:value={bug.status} class="form-input">
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
          <span>{submitting ? "Guardando..." : "Guardar cambios"}</span>
        </button>
      </div>
    </form>
    <Comments refId={bug.id} refTable="bugs" />
  {/if}
</div>
