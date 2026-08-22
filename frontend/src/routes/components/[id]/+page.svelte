<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import {
    ArrowLeft,
    Plus,
    FlaskConical,
    ChevronRight,
    Pencil,
    Trash2,
    Save,
    X,
    Link2,
    Network,
    GitBranch,
  } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import Modal from "$lib/components/ui/Modal.svelte";
  import ComponentGraph from "$lib/components/flow/ComponentGraph.svelte";
  import {
    getComponent,
    getComponents,
    updateComponent,
    deleteComponent,
    getComponentTestCases,
    getComponentChildren,
    getComponentDependencies,
    attachComponentDependency,
    detachComponentDependency,
  } from "$lib/api/components.js";
  import Comments from "$lib/components/Comments.svelte";

  let loading = $state(true);
  let error = $state("");
  let submitting = $state(false);
  let component = $state(null);
  let testCases = $state([]);
  let allComponents = $state([]);
  let children = $state([]);
  let dependencies = $state([]);
  let editing = $state(false);

  let depDraft = $state({ depends_on_id: "", criticality: "optional" });
  let graphOpen = $state(false);

  const statusMap = {
    untested: { label: "Sin probar", cls: "status-todo" },
    passed: { label: "Aprobado", cls: "status-done" },
    failed: { label: "Fallido", cls: "status-blocked" },
    blocked: { label: "Bloqueado", cls: "status-progress" },
  };

  const typeOptions = ["module", "controller", "model", "view", "function", "service", "other"];

  let possibleParents = $derived(
    component ? allComponents.filter((c) => String(c.id) !== String(component.id)) : [],
  );
  let dependencyCandidates = $derived(
    component ? allComponents.filter((c) => String(c.id) !== String(component.id)) : [],
  );

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const id = window.location.pathname.split("/").filter(Boolean).pop();
      const [compRes, tcRes, allRes, childRes, depRes] = await Promise.all([
        getComponent(id),
        getComponentTestCases(id),
        getComponents({ per_page: 100 }),
        getComponentChildren(id),
        getComponentDependencies(id),
      ]);
      component = compRes?.data ?? compRes;
      testCases = tcRes?.data ?? tcRes ?? [];
      allComponents = allRes?.data ?? allRes ?? [];
      children = childRes?.data ?? childRes ?? [];
      dependencies = Array.isArray(depRes) ? depRes : depRes?.data ?? [];
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando componente";
    } finally {
      loading = false;
    }
  }

  async function loadDependencies() {
    const res = await getComponentDependencies(component.id);
    dependencies = Array.isArray(res) ? res : res?.data ?? [];
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
        type: component.type || null,
        description: component.description || null,
        parent_id: component.parent_id ? Number(component.parent_id) : null,
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
      alert(e?.response?.data?.error ?? e?.message ?? "Error al eliminar");
    }
  }

  async function handleAddDependency() {
    if (!depDraft.depends_on_id) return;
    error = "";
    try {
      await attachComponentDependency(component.id, {
        depends_on_id: Number(depDraft.depends_on_id),
        criticality: depDraft.criticality,
      });
      depDraft = { depends_on_id: "", criticality: "optional" };
      await loadDependencies();
    } catch (e) {
      alert(e?.response?.data?.error ?? e?.message ?? "Error al agregar dependencia");
    }
  }

  async function handleRemoveDependency(dep) {
    if (!confirm(`¿Quitar la dependencia hacia "${dep.name}"?`)) return;
    try {
      await detachComponentDependency(component.id, dep.id);
      dependencies = dependencies.filter((d) => d.id !== dep.id);
    } catch (e) {
      alert(e?.response?.data?.error ?? e?.message ?? "Error al quitar dependencia");
    }
  }

  function criticalityColor(c) {
    return c === "critical" ? "status-blocked" : "status-todo";
  }

  onMount(load);
</script>

<div class="max-w-5xl mx-auto p-6 space-y-8">
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
        <div class="flex flex-wrap items-center gap-2 mt-1">
          <span class="status-badge status-todo">{component.type || "sin tipo"}</span>
          {#if (component.critical_dependency_count ?? 0) > 0}
            <span class="status-badge status-blocked">impacto</span>
          {/if}
        </div>
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
            onclick={() => (graphOpen = true)}
            class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-indigo-500 hover:bg-indigo-500/10 rounded-lg transition-colors"
            title="Ver grafo de dependientes"
          >
            <Network size={20} />
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

    {#if editing}
      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6">
        <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark border-b border-border-light dark:border-border-dark pb-2">
          Editar detalles
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label for="type" class="block text-sm font-medium">Tipo</label>
            <select id="type" bind:value={component.type} class="form-input">
              <option value="">Sin tipo</option>
              {#each typeOptions as t}
                <option value={t}>{t}</option>
              {/each}
            </select>
          </div>
          <div class="space-y-2">
            <label for="parent_id" class="block text-sm font-medium">Componente padre</label>
            <select id="parent_id" bind:value={component.parent_id} class="form-input">
              <option value="">Sin padre</option>
              {#each possibleParents as p}
                <option value={p.id}>{p.name} (#{p.id})</option>
              {/each}
            </select>
          </div>
        </div>
        <div class="space-y-2">
          <label for="description" class="block text-sm font-medium">Descripción</label>
          <textarea id="description" rows="3" bind:value={component.description} class="form-input resize-none" placeholder="Descripción"></textarea>
        </div>
      </div>
    {:else}
      {#if component.description}
        <p class="text-text-sec-light dark:text-text-sec-dark -mt-4">{component.description}</p>
      {/if}
    {/if}

    <!-- Dependencias -->
    <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark flex items-center gap-2">
          <Link2 size={18} class="text-indigo-500" />
          Dependencias
          <span class="text-sm font-normal text-text-sec-light dark:text-text-sec-dark">({dependencies.length})</span>
        </h2>
        <button
          type="button"
          onclick={() => (graphOpen = true)}
          class="text-sm text-indigo-500 hover:text-indigo-400 font-medium flex items-center gap-1"
        >
          <Network size={16} /> Ver dependientes
        </button>
      </div>

      <div class="flex flex-wrap items-end gap-3">
        <div class="space-y-1 flex-1 min-w-[200px]">
          <label for="depends_on" class="block text-xs font-medium text-text-sec-light dark:text-text-sec-dark">Depende de</label>
          <select id="depends_on" bind:value={depDraft.depends_on_id} class="form-input">
            <option value="">Seleccioná un componente</option>
            {#each dependencyCandidates as cand}
              <option value={cand.id}>{cand.name} (#{cand.id})</option>
            {/each}
          </select>
        </div>
        <div class="space-y-1">
          <label for="criticality" class="block text-xs font-medium text-text-sec-light dark:text-text-sec-dark">Criticidad</label>
          <select id="criticality" bind:value={depDraft.criticality} class="form-input w-auto">
            <option value="optional">optional</option>
            <option value="critical">critical</option>
          </select>
        </div>
        <button
          type="button"
          onclick={handleAddDependency}
          disabled={!depDraft.depends_on_id}
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-lg font-medium transition-colors"
        >
          <Plus size={16} /> Agregar
        </button>
      </div>

      <div class="space-y-2">
        {#each dependencies as dep}
          <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 border border-border-light dark:border-border-dark rounded-lg">
            <div class="flex items-center gap-2">
              <span class="font-medium text-text-main-light dark:text-text-main-dark">{dep.name}</span>
              <span class="status-badge {criticalityColor(dep.criticality)}">{dep.criticality || "optional"}</span>
            </div>
            <button
              type="button"
              onclick={() => handleRemoveDependency(dep)}
              class="p-1.5 text-text-sec-light dark:text-text-sec-dark hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors"
              title="Quitar"
            >
              <Trash2 size={16} />
            </button>
          </div>
        {:else}
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
            Este componente todavía no tiene dependencias.
          </p>
        {/each}
      </div>
    </div>

    <!-- Hijos -->
    <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-3">
      <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark flex items-center gap-2">
        <GitBranch size={18} class="text-indigo-500" />
        Hijos
        <span class="text-sm font-normal text-text-sec-light dark:text-text-sec-dark">({children.length})</span>
      </h2>
      <div class="grid gap-2">
        {#each children as child}
          <a href="/components/{child.id}" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 border border-border-light dark:border-border-dark rounded-lg hover:border-indigo-500/50 transition-colors group">
            <div class="flex items-center gap-2">
              <span class="font-medium text-text-main-light dark:text-text-main-dark group-hover:text-indigo-500">{child.name}</span>
            </div>
            <ChevronRight size={16} class="text-text-sec-light dark:text-text-sec-dark" />
          </a>
        {:else}
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">No tiene hijos.</p>
        {/each}
      </div>
    </div>

    <!-- Test Cases -->
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
            <span class="status-badge {sm.cls}">{sm.label}</span>
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

    <Comments refId={component.id} refTable="components" />
  {/if}
</div>

<Modal open={graphOpen} size="xl" on:close={() => (graphOpen = false)}>
  <div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark">
        Dependientes de {component?.name}
      </h3>
      <button
        type="button"
        onclick={() => (graphOpen = false)}
        class="p-1.5 text-text-sec-light dark:text-text-sec-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
      >
        <X size={18} />
      </button>
    </div>
    {#if component}
      <ComponentGraph component={{ id: component.id, name: component.name }} />
    {/if}
  </div>
</Modal>
