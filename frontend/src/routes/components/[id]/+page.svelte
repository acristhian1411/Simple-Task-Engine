<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Plus, FlaskConical, ChevronRight, Pencil, Trash2, Save, X } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getComponent, updateComponent, deleteComponent, getComponentTestCases } from "$lib/api/components.js";

  let loading = $state(true);
  let error = $state("");
  let component = $state(null);
  let testCases = $state([]);
  let editing = $state(false);
  let submitting = $state(false);

  const statusMap = {
    untested: { label: "Sin probar", cls: "status-todo" },
    passed: { label: "Aprobado", cls: "status-done" },
    failed: { label: "Fallido", cls: "status-blocked" },
    blocked: { label: "Bloqueado", cls: "status-progress" },
  };

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const id = window.location.pathname.split("/").filter(Boolean).pop();
      const [compRes, tcRes] = await Promise.all([
        getComponent(id),
        getComponentTestCases(id),
      ]);
      component = compRes?.data ?? compRes;
      testCases = tcRes?.data ?? tcRes ?? [];
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando componente";
    } finally {
      loading = false;
    }
  }

  async function handleSave() {
    if (!component.name) {
      error = "El nombre es obligatorio";
      return;
    }
    submitting = true;
    error = "";
    try {
      await updateComponent(component.id, {
        name: component.name,
        description: component.description || null,
      });
      editing = false;
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al guardar";
    } finally {
      submitting = false;
    }
  }

  async function handleDelete() {
    if (!confirm(`¿Eliminar el componente "${component.name}"?`)) return;
    try {
      await deleteComponent(component.id);
      goto("/components");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al eliminar";
    }
  }

  onMount(load);
</script>

<div class="max-w-4xl mx-auto p-6 space-y-8">
  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else if error && !component}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {:else if component}
    <div class="flex items-center gap-4">
      <a href="/components" class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
        <ArrowLeft size={20} />
      </a>
      <div class="flex-1">
        {#if editing}
          <input type="text" bind:value={component.name} class="form-input text-xl font-bold" />
        {:else}
          <h1 class="text-3xl font-bold text-text-main-light dark:text-text-main-dark">{component.name}</h1>
        {/if}
        {#if editing}
          <textarea rows="2" bind:value={component.description} class="form-input mt-2 resize-none" placeholder="Descripción"></textarea>
        {:else}
          <p class="text-text-sec-light dark:text-text-sec-dark mt-1">
            {component.description || "Sin descripción"}
          </p>
        {/if}
      </div>
      <div class="flex gap-2">
        {#if editing}
          <button
            type="button"
            onclick={handleSave}
            disabled={submitting}
            class="flex items-center gap-1 p-2 text-indigo-600 hover:bg-indigo-500/10 rounded-lg transition-colors"
            title="Guardar"
          >
            <Save size={20} />
          </button>
          <button
            type="button"
            onclick={() => {
              editing = false;
              error = "";
            }}
            class="p-2 text-text-sec-light dark:text-text-sec-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
            title="Cancelar"
          >
            <X size={20} />
          </button>
        {:else}
          <button
            type="button"
            onclick={() => (editing = true)}
            class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500 hover:bg-indigo-500/10 rounded-lg transition-colors"
            title="Editar"
          >
            <Pencil size={20} />
          </button>
          <button
            type="button"
            onclick={handleDelete}
            class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors"
            title="Eliminar"
          >
            <Trash2 size={20} />
          </button>
        {/if}
      </div>
    </div>

    {#if error}
      <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
    {/if}

    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-text-main-light dark:text-text-main-dark flex items-center gap-2">
          <FlaskConical size={20} class="text-indigo-500" />
          Test Cases
          <span class="text-sm font-normal text-text-sec-light dark:text-text-sec-dark">({testCases.length})</span>
        </h2>
        <a
          href="/tests/new?componentId={component.id}"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
        >
          <Plus size={18} />
          <span>Nuevo Test Case</span>
        </a>
      </div>

      <div class="grid gap-3">
        {#each testCases as testCase}
          {@const sm = statusMap[testCase.status] ?? { label: testCase.status || "Sin probar", cls: "status-todo" }}
          <a
            href="/tests/{testCase.id}"
            class="group flex items-center justify-between p-4 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl hover:border-indigo-500/50 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200"
          >
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-text-sec-light dark:text-text-sec-dark group-hover:text-indigo-500 group-hover:bg-indigo-500/10 transition-colors">
                <span class="font-mono font-bold text-sm">#{testCase.id}</span>
              </div>
              <div>
                <h3 class="font-medium text-text-main-light dark:text-text-main-dark group-hover:underline">{testCase.title}</h3>
                <p class="text-sm text-text-sec-light dark:text-text-sec-dark line-clamp-1">
                  {testCase.description || "Sin descripción"}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="status-badge {sm.cls}">{sm.label}</span>
              <div class="text-text-sec-light dark:text-text-sec-dark group-hover:text-indigo-500 transition-colors">
                <ChevronRight size={20} />
              </div>
            </div>
          </a>
        {:else}
          <div class="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-border-light dark:border-border-dark rounded-xl">
            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-full mb-3">
              <FlaskConical size={24} class="text-text-sec-light dark:text-text-sec-dark" />
            </div>
            <p class="text-text-main-light dark:text-text-main-dark font-medium">Todavía no hay test cases</p>
            <p class="text-sm text-text-sec-light dark:text-text-sec-dark mt-1">
              Creá un test case para verificar este componente.
            </p>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>
