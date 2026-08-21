<script>
  import Modal from "$lib/components/ui/Modal.svelte";
  import { createEventDispatcher } from "svelte";
  import { boardStore } from "$lib/stores/board.js";
  import { updateTask } from "$lib/api/tasks.js";
  import {
    getTaskSubtasks,
    createSubtask,
    updateSubtask,
    deleteSubtask,
  } from "$lib/api/subtasks.js";
  import {
    getTaskDependencies,
    createDependency,
    deleteDependency,
  } from "$lib/api/dependencies.js";
  import {
    attachTaskComponent,
    detachTaskComponent,
    attachTaskBug,
    detachTaskBug,
  } from "$lib/api/tasks.js";
  import { getComponents } from "$lib/api/components.js";
  import { getBugs } from "$lib/api/bugs.js";
  import { unwrapList } from "$lib/api/http.js";

  export let open = false;
  export let task = null;

  const dispatch = createEventDispatcher();

  let subtasks = [];
  let dependencies = [];
  let newSubtaskTitle = "";
  let addingSubtask = false;
  let subtaskBusy = false;
  let subtaskError = "";
  let dependencyTaskId = "";
  let dependencyBusy = false;
  let dependencyError = "";
  let depSearch = "";
  let depSearchFocus = false;
  $: showDepResults = depSearchFocus || depSearch.trim() !== "";
  let taskComponents = [];
  let taskBugs = [];
  let componentsOptions = [];
  let bugsOptions = [];
  let selectedComponentId = "";
  let componentBusy = false;
  let componentError = "";
  let selectedBugId = "";
  let bugRelation = "related";
  let bugBusy = false;
  let bugError = "";
  let editingTitle = false;
  let titleDraft = "";
  let editingDesc = false;
  let descDraft = "";
  let saveError = "";
  let loadingDetails = false;
  let loadedTaskId = null;

  const lists = $boardStore.lists;

  $: allTasks = lists.flatMap((l) =>
    (l.tasks || []).map((t) => ({
      ...t,
      listTitle: l.title,
      list_id: l.id,
    })),
  );

  // Candidatas para dependencias (excluye la propia tarea), filtradas por la búsqueda
  $: depCandidates = allTasks.filter((t) => {
    if (String(t.id) === String(task?.id)) return false;
    const q = depSearch.trim().toLowerCase();
    if (!q) return true;
    return (
      (t.title || "").toLowerCase().includes(q) ||
      String(t.id).includes(q)
    );
  });

  let depSelectedTask = null;
  $: depSelectedTask = dependencyTaskId
    ? allTasks.find((x) => String(x.id) === String(dependencyTaskId)) || null
    : null;


  $: if (open && task && task.id !== loadedTaskId) {
    loadedTaskId = task.id;
    resetState();
    loadDetails();
  }

  function close() {
    dispatch("close");
  }

  function resetState() {
    subtasks = [];
    dependencies = [];
    taskComponents = [];
    taskBugs = [];
    newSubtaskTitle = "";
    addingSubtask = false;
    subtaskError = "";
    dependencyTaskId = "";
    dependencyError = "";
    depSearch = "";
    depSearchFocus = false;
    selectedComponentId = "";
    componentError = "";
    selectedBugId = "";
    bugRelation = "related";
    bugError = "";
    editingTitle = false;
    editingDesc = false;
    saveError = "";
    loadingDetails = false;
  }

  async function loadDetails() {
    if (!task) return;
    loadingDetails = true;
    try {
      const [subs, deps, comps, bugs] = await Promise.all([
        getTaskSubtasks(task.id),
        getTaskDependencies(task.id),
        getComponents({ per_page: 100 }),
        getBugs({ per_page: 100 }),
      ]);
      subtasks = Array.isArray(subs) ? subs : [];
      dependencies = Array.isArray(deps) ? deps : [];
      taskComponents = Array.isArray(task.components) ? task.components : [];
      taskBugs = Array.isArray(task.bugs) ? task.bugs : [];
      componentsOptions = unwrapList(comps);
      bugsOptions = unwrapList(bugs);
      titleDraft = task.title || "";
      descDraft = task.description || "";
    } catch (e) {
      saveError =
        e?.response?.data?.message ?? e?.message ?? "Error cargando detalles";
    } finally {
      loadingDetails = false;
    }
  }

  function startEditTitle() {
    editingTitle = true;
    titleDraft = task.title || "";
  }

  async function saveTitle() {
    if (titleDraft.trim() && titleDraft.trim() !== task.title) {
      try {
        const updated = await boardStore.changeTask(task.id, {
          title: titleDraft.trim(),
        });
        task.title = updated.title ?? titleDraft.trim();
      } catch (e) {
        saveError =
          e?.response?.data?.message ?? e?.message ?? "Error al guardar";
      }
    }
    editingTitle = false;
  }

  function startEditDesc() {
    editingDesc = true;
    descDraft = task.description || "";
  }

  async function saveDesc() {
    try {
      const updated = await boardStore.changeTask(task.id, {
        description: descDraft || null,
      });
      task.description = updated.description ?? descDraft;
    } catch (e) {
      saveError =
        e?.response?.data?.message ?? e?.message ?? "Error al guardar";
    }
    editingDesc = false;
  }

  async function toggleSubtask(st) {
    const next = !st.is_completed;
    try {
      await updateSubtask(st.id, { is_completed: next });
      st.is_completed = next;
    } catch (e) {
      saveError =
        e?.response?.data?.message ?? e?.message ?? "Error al actualizar";
    }
  }

  function startAddSubtask() {
    addingSubtask = true;
    newSubtaskTitle = "";
    subtaskError = "";
  }

  async function submitSubtask() {
    if (!newSubtaskTitle.trim() || subtaskBusy) return;
    subtaskBusy = true;
    subtaskError = "";
    try {
      const res = await createSubtask({
        task_id: task.id,
        title: newSubtaskTitle.trim(),
      });
      const created = res?.data ?? res;
      subtasks = [...subtasks, created];
      newSubtaskTitle = "";
      addingSubtask = false;
    } catch (e) {
      subtaskError =
        e?.response?.data?.message ?? e?.message ?? "Error al crear subtarea";
    } finally {
      subtaskBusy = false;
    }
  }

  async function removeSubtask(st) {
    if (!confirm(`¿Eliminar la subtarea "${st.title}"?`)) return;
    try {
      await deleteSubtask(st.id);
      subtasks = subtasks.filter((x) => x.id !== st.id);
    } catch (e) {
      saveError =
        e?.response?.data?.message ?? e?.message ?? "Error al eliminar";
    }
  }

  async function submitDependency() {
    if (!dependencyTaskId || dependencyBusy) return;
    if (String(dependencyTaskId) === String(task.id)) {
      dependencyError = "No se puede depender de la misma tarea";
      return;
    }
    dependencyError = "";
    try {
      const res = await createDependency({
        task_id: task.id,
        depends_on_task_id: dependencyTaskId,
      });
      const created = res?.data ?? res;
      dependencies = [...dependencies, created];
      dependencyTaskId = "";
      depSearch = "";
      depSelectedTask = null;
      depSearchFocus = false;
    } catch (e) {
      dependencyError =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "Error al crear dependencia";
    } finally {
      dependencyBusy = false;
    }
  }

  function selectDepTask(t) {
    dependencyTaskId = t.id;
    depSearch = t.title;
    dependencyError = "";
    depSearchFocus = false;
  }

  function clearDepSelection() {
    dependencyTaskId = "";
    depSearch = "";
    depSearchFocus = true;
  }

  function onDepSearchInput() {
    if (depSearch.trim() !== "") {
      dependencyTaskId = "";
    }
  }

  function onDepSearchFocus() {
    depSearchFocus = true;
  }

  function onDepSearchKeydown(e) {
    if (e.key === "Escape") {
      depSearchFocus = false;
    }
  }

  function onDepSearchBlur() {
    setTimeout(() => {
      depSearchFocus = false;
    }, 150);
  }

  async function removeDependency(dep) {
    try {
      await deleteDependency(dep.id);
      dependencies = dependencies.filter((x) => x.id !== dep.id);
    } catch (e) {
      saveError =
        e?.response?.data?.message ?? e?.message ?? "Error al eliminar";
    }
  }

  function depTargetTitle(dep) {
    const t = allTasks.find((x) => String(x.id) === String(dep.depends_on_task_id));
    return t ? t.title : `Tarea #${dep.depends_on_task_id}`;
  }

  async function submitComponent() {
    if (!selectedComponentId || componentBusy) return;
    componentBusy = true;
    componentError = "";
    try {
      const cid = Number(selectedComponentId);
      const res = await attachTaskComponent(task.id, cid);
      const ok = res?.data === "ok" || res?.data === true;
      if (!taskComponents.some((x) => String(x.id) === String(cid))) {
        const comp = componentsOptions.find(
          (x) => String(x.id) === String(cid),
        );
        taskComponents = [...taskComponents, comp || { id: cid }];
      }
      selectedComponentId = "";
    } catch (e) {
      componentError =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "Error al vincular componente";
    } finally {
      componentBusy = false;
    }
  }

  async function removeComponent(comp) {
    try {
      await detachTaskComponent(task.id, comp.id);
      taskComponents = taskComponents.filter(
        (x) => String(x.id) !== String(comp.id),
      );
    } catch (e) {
      componentError =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "Error al desvincular componente";
    }
  }

  async function submitBug() {
    if (!selectedBugId || bugBusy) return;
    bugBusy = true;
    bugError = "";
    try {
      const bid = Number(selectedBugId);
      await attachTaskBug(task.id, bid, bugRelation);
      if (!taskBugs.some((x) => String(x.id) === String(bid))) {
        const bug = bugsOptions.find((x) => String(x.id) === String(bid));
        const base = bug
          ? { ...bug, pivot: { relation_type: bugRelation } }
          : { id: bid };
        taskBugs = [...taskBugs, base];
      } else {
        taskBugs = taskBugs.map((x) =>
          String(x.id) === String(bid)
            ? { ...x, pivot: { relation_type: bugRelation } }
            : x,
        );
      }
      selectedBugId = "";
    } catch (e) {
      bugError =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "Error al vincular bug";
    } finally {
      bugBusy = false;
    }
  }

  async function removeBug(bug) {
    try {
      await detachTaskBug(task.id, bug.id);
      taskBugs = taskBugs.filter((x) => String(x.id) !== String(bug.id));
    } catch (e) {
      bugError =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "Error al desvincular bug";
    }
  }

  function bugRelationLabel(rel) {
    return (
      {
        fixes: "Corrige",
        blocked_by: "Bloqueado por",
        related: "Relacionado",
      }[rel] || "Relacionado"
    );
  }

  let completedCount = 0;
  $: completedCount = subtasks.filter((s) => s.is_completed).length;
</script>

<Modal {open} size="xl" position="right" on:close={close}>
  {#if task}
    <div
      class="h-full w-full flex flex-col bg-background-light dark:bg-background-dark"
    >
      <!-- Header -->
      <div
        class="h-16 px-6 border-b flex items-center justify-between bg-surface-light/50 dark:bg-background-dark/95 shrink-0"
      >
        <div
          class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400"
        >
          <span class="material-symbols-outlined">view_kanban</span>
          <div class="flex flex-col">
            <div class="text-sm font-semibold">#{task.id}</div>
            <div class="text-xs text-gray-400">{task.listTitle ?? ""}</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button class="p-2" on:click={close} aria-label="Cerrar">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-[800px] mx-auto flex flex-col gap-8">
          {#if saveError}
            <div
              class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200"
            >
              {saveError}
            </div>
          {/if}

          <!-- Título editable -->
          <div>
            {#if editingTitle}
              <div class="flex flex-col gap-2">
                <input
                  type="text"
                  bind:value={titleDraft}
                  on:keydown={(e) => e.key === "Enter" && saveTitle()}
                  class="w-full px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                />
                <div class="flex gap-2">
                  <button
                    class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors"
                    on:click={saveTitle}
                  >
                    Guardar
                  </button>
                  <button
                    class="px-3 py-1.5 text-sm text-gray-500 hover:bg-gray-100 dark:hover:bg-surface-dark rounded-lg transition-colors"
                    on:click={() => (editingTitle = false)}
                  >
                    Cancelar
                  </button>
                </div>
              </div>
            {:else}
              <div class="group flex items-start gap-2">
                <h1
                  class="text-3xl font-bold text-gray-900 dark:text-white flex-1"
                >
                  {task.title}
                </h1>
                <button
                  class="p-2 text-gray-400 hover:text-primary transition-colors opacity-0 group-hover:opacity-100"
                  on:click={startEditTitle}
                  aria-label="Editar título"
                >
                  <span class="material-symbols-outlined text-[18px]">edit</span>
                </button>
              </div>
            {/if}
          </div>

          <!-- Descripción editable -->
          <div>
            <h2
              class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-gray-400"
                >description</span
              >Descripción
            </h2>
            <div class="mt-3">
              {#if editingDesc}
                <div class="flex flex-col gap-2">
                  <textarea
                    bind:value={descDraft}
                    rows="4"
                    class="w-full px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                  ></textarea>
                  <div class="flex gap-2">
                    <button
                      class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors"
                      on:click={saveDesc}
                    >
                      Guardar
                    </button>
                    <button
                      class="px-3 py-1.5 text-sm text-gray-500 hover:bg-gray-100 dark:hover:bg-surface-dark rounded-lg transition-colors"
                      on:click={() => (editingDesc = false)}
                    >
                      Cancelar
                    </button>
                  </div>
                </div>
              {:else}
                <button
                  type="button"
                  class="group w-full text-left flex items-start gap-2 bg-surface-light dark:bg-surface-dark border rounded-xl p-4 hover:border-primary/50 transition-colors"
                  on:click={startEditDesc}
                >
                  <div class="flex-1">
                    {#if task.description}
                      <div
                        class="text-gray-600 dark:text-gray-300 text-sm whitespace-pre-wrap"
                      >
                        {task.description}
                      </div>
                    {:else}
                      <em class="text-gray-400 text-sm"
                        >No hay descripción. Hacé click para agregar.</em
                      >
                    {/if}
                  </div>
                  <span
                    class="material-symbols-outlined text-gray-400 text-[18px] opacity-0 group-hover:opacity-100"
                    >edit</span
                  >
                </button>
              {/if}
            </div>
          </div>

          <!-- Subtareas -->
          <div>
            <div class="flex items-center justify-between">
              <h2
                class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
              >
                <span class="material-symbols-outlined text-gray-400"
                  >check_box</span
                >Subtareas
              </h2>
              <span class="text-xs text-gray-500"
                >{completedCount}/{subtasks.length}</span
              >
            </div>

            <div class="mt-3 space-y-2">
              {#if loadingDetails}
                <div class="text-sm text-gray-500 italic">
                  Cargando subtareas...
                </div>
              {:else if subtasks.length}
                {#each subtasks as st (st.id)}
                  <div
                    class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-surface-dark/50 transition-colors group"
                  >
                    <div class="flex items-center mt-0.5">
                      <input
                        type="checkbox"
                        checked={st.is_completed}
                        on:change={() => toggleSubtask(st)}
                        class="w-5 h-5"
                      />
                    </div>
                    <div
                      class="flex-1 text-sm {st.is_completed
                        ? 'line-through text-gray-400'
                        : ''}"
                    >
                      {st.title}
                    </div>
                    <button
                      class="p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-colors"
                      on:click={() => removeSubtask(st)}
                      aria-label="Eliminar subtarea"
                    >
                      <span class="material-symbols-outlined text-[18px]"
                        >delete</span
                      >
                    </button>
                  </div>
                {/each}
              {:else}
                <div class="text-sm text-gray-500 italic">Sin subtareas</div>
              {/if}

              {#if addingSubtask}
                <div class="flex flex-col gap-2 pt-1">
                  <input
                    type="text"
                    placeholder="Nombre de la subtarea"
                    bind:value={newSubtaskTitle}
                    on:keydown={(e) => e.key === "Enter" && submitSubtask()}
                    class="w-full px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                  />
                  {#if subtaskError}
                    <p class="text-xs text-red-500">{subtaskError}</p>
                  {/if}
                  <div class="flex items-center gap-2">
                    <button
                      class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
                      on:click={submitSubtask}
                      disabled={subtaskBusy}
                    >
                      {subtaskBusy ? "Añadiendo..." : "Añadir"}
                    </button>
                    <button
                      class="p-1.5 text-gray-500 hover:text-gray-700 transition-colors"
                      on:click={() => (addingSubtask = false)}
                      aria-label="Cancelar"
                    >
                      <span class="material-symbols-outlined">close</span>
                    </button>
                  </div>
                </div>
              {:else}
                <button
                  class="mt-2 flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-primary hover:bg-gray-100 dark:hover:bg-surface-dark rounded-lg transition-colors"
                  on:click={startAddSubtask}
                >
                  <span class="material-symbols-outlined text-[18px]">add</span>
                  Añadir subtarea
                </button>
              {/if}
            </div>
          </div>

          <!-- Dependencias -->
          <div>
            <h2
              class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-gray-400">link</span
              >Dependencias
            </h2>
            <div class="mt-3 space-y-2">
              {#if loadingDetails}
                <div class="text-sm text-gray-500 italic">
                  Cargando dependencias...
                </div>
              {:else if dependencies.length}
                {#each dependencies as dep (dep.id)}
                  <div
                    class="bg-white dark:bg-background-dark border rounded-md p-3 flex items-center justify-between shadow-sm group"
                  >
                    <div class="flex items-center gap-3">
                      <div
                        class="h-8 w-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-xs"
                      >
                        {dep.depends_on_task_id}
                      </div>
                      <div>
                        <p class="text-sm font-medium">
                          {depTargetTitle(dep)}
                        </p>
                        <div class="text-xs text-gray-500">
                          Tarea #{dep.depends_on_task_id}
                        </div>
                      </div>
                    </div>
                    <button
                      class="p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-colors"
                      on:click={() => removeDependency(dep)}
                      aria-label="Eliminar dependencia"
                    >
                      <span class="material-symbols-outlined text-[18px]"
                        >delete</span
                      >
                    </button>
                  </div>
                {/each}
              {:else}
                <div class="text-sm text-gray-500 italic">
                  Sin dependencias
                </div>
              {/if}

              <div class="flex flex-col gap-2 pt-1">
                <div class="flex items-center gap-2">
                  <div class="relative flex-1">
                    <div class="flex items-center gap-2">
                      <div class="flex-1">
                        {#if depSelectedTask}
                          <div
                            class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm"
                          >
                            <span class="flex-1 truncate">
                              {depSelectedTask.title}{depSelectedTask.listTitle
                                ? ` (${depSelectedTask.listTitle})`
                                : ""}
                            </span>
                            <button
                              class="p-0.5 text-gray-400 hover:text-red-500 transition-colors"
                              on:click={clearDepSelection}
                              aria-label="Quitar selección"
                            >
                              <span
                                class="material-symbols-outlined text-[18px]"
                                >close</span
                              >
                            </button>
                          </div>
                        {:else}
                          <input
                            type="text"
                            bind:value={depSearch}
                            on:focus={onDepSearchFocus}
                            on:input={onDepSearchInput}
                            on:blur={onDepSearchBlur}
                            on:keydown={onDepSearchKeydown}
                            placeholder="Buscar tarea por título o #id..."
                            class="w-full px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                          />
                          {#if showDepResults}
                            <div
                              class="absolute z-20 mt-1 w-full bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg shadow-lg max-h-48 overflow-y-auto"
                            >
                              {#if depCandidates.length}
                                {#each depCandidates as c (c.id)}
                                  <button
                                    type="button"
                                    class="block w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm transition-colors"
                                    on:mousedown|preventDefault
                                    on:click={() => selectDepTask(c)}
                                  >
                                    <span class="block truncate font-medium">
                                      {c.title || "(sin título)"}
                                    </span>
                                    <span class="block text-xs text-gray-500">
                                      #{c.id}
                                      {c.listTitle ? ` · ${c.listTitle}` : ""}
                                    </span>
                                  </button>
                                {/each}
                              {:else}
                                <div class="px-3 py-2 text-sm text-gray-500 italic">
                                  Sin resultados
                                </div>
                              {/if}
                            </div>
                          {/if}
                        {/if}
                      </div>
                    </div>
                  </div>
                  <button
                    class="px-3 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors shrink-0"
                    on:click={submitDependency}
                    disabled={dependencyBusy}
                  >
                    {dependencyBusy ? "Añadiendo..." : "Añadir"}
                  </button>
                </div>
                {#if dependencyError}
                  <p class="text-xs text-red-500">{dependencyError}</p>
                {/if}
              </div>
            </div>
          </div>

          <!-- Componentes -->
          <div>
            <h2
              class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-gray-400"
                >widgets</span
              >Componentes
            </h2>
            <div class="mt-3 space-y-2">
              {#if loadingDetails}
                <div class="text-sm text-gray-500 italic">
                  Cargando componentes...
                </div>
              {:else if taskComponents.length}
                {#each taskComponents as comp (comp.id)}
                  <div
                    class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-surface-dark/50 transition-colors group"
                  >
                    <div class="flex items-center gap-2 min-w-0">
                      <span
                        class="h-6 w-6 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-300"
                      >
                        <span class="material-symbols-outlined text-[16px]"
                          >widgets</span
                        >
                      </span>
                      <span class="text-sm font-medium truncate"
                        >{comp.name || `#${comp.id}`}</span
                      >
                    </div>
                    <button
                      class="p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-colors"
                      on:click={() => removeComponent(comp)}
                      aria-label="Quitar componente"
                    >
                      <span class="material-symbols-outlined text-[18px]"
                        >delete</span
                      >
                    </button>
                  </div>
                {/each}
              {:else}
                <div class="text-sm text-gray-500 italic">Sin componentes</div>
              {/if}

              <div class="flex items-center gap-2 pt-1">
                <select
                  bind:value={selectedComponentId}
                  class="flex-1 px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">Seleccionar componente...</option>
                  {#each componentsOptions as c (c.id)}
                    <option value={c.id}>{c.name || `#${c.id}`}</option>
                  {/each}
                </select>
                <button
                  class="px-3 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors shrink-0"
                  on:click={submitComponent}
                  disabled={componentBusy}
                >
                  {componentBusy ? "Añadiendo..." : "Añadir"}
                </button>
              </div>
              {#if componentError}
                <p class="text-xs text-red-500">{componentError}</p>
              {/if}
            </div>
          </div>

          <!-- Bugs -->
          <div>
            <h2
              class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-gray-400">bug_report</span
              >Bugs
            </h2>
            <div class="mt-3 space-y-2">
              {#if loadingDetails}
                <div class="text-sm text-gray-500 italic">
                  Cargando bugs...
                </div>
              {:else if taskBugs.length}
                {#each taskBugs as bug (bug.id)}
                  <div
                    class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-surface-dark/50 transition-colors group"
                  >
                    <div class="flex items-center gap-2 min-w-0">
                      <span
                        class="h-6 w-6 rounded bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-300 shrink-0"
                      >
                        <span class="material-symbols-outlined text-[16px]"
                          >bug_report</span
                        >
                      </span>
                      <div class="min-w-0">
                        <p class="text-sm font-medium truncate"
                          >{bug.title || `Bug #${bug.id}`}</p>
                        <p class="text-xs text-gray-500">
                          {bugRelationLabel(bug.pivot?.relation_type)}
                          {#if bug.severity}· {bug.severity}{/if}
                          {#if bug.status}· {bug.status}{/if}
                        </p>
                      </div>
                    </div>
                    <button
                      class="p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-colors"
                      on:click={() => removeBug(bug)}
                      aria-label="Quitar bug"
                    >
                      <span class="material-symbols-outlined text-[18px]"
                        >delete</span
                      >
                    </button>
                  </div>
                {/each}
              {:else}
                <div class="text-sm text-gray-500 italic">Sin bugs</div>
              {/if}

              <div class="flex flex-col gap-2 pt-1">
                <div class="flex items-center gap-2">
                  <select
                    bind:value={selectedBugId}
                    class="flex-1 px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                  >
                    <option value="">Seleccionar bug...</option>
                    {#each bugsOptions as b (b.id)}
                      <option value={b.id}
                        >{b.title || `Bug #${b.id}`}
                        {b.severity ? ` · ${b.severity}` : ""}</option
                      >
                    {/each}
                  </select>
                  <select
                    bind:value={bugRelation}
                    class="px-3 py-2 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                  >
                    <option value="related">Relacionado</option>
                    <option value="fixes">Corrige</option>
                    <option value="blocked_by">Bloqueado por</option>
                  </select>
                  <button
                    class="px-3 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors shrink-0"
                    on:click={submitBug}
                    disabled={bugBusy}
                  >
                    {bugBusy ? "Añadiendo..." : "Añadir"}
                  </button>
                </div>
                {#if bugError}
                  <p class="text-xs text-red-500">{bugError}</p>
                {/if}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  {/if}
</Modal>
