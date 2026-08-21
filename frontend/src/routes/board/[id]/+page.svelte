<script>
  import { onMount } from "svelte";
  import { page } from "$app/stores";
  import { goto } from "$app/navigation";
  import { refreshMe } from "$lib/stores/auth.js";
  import { boardStore } from "$lib/stores/board.js";
  import KanbanColumn from "$lib/components/kanban/KanbanColumn.svelte";
  import TaskDetails from "$lib/components/TaskDetails.svelte";

  $: board = $boardStore.board;
  $: lists = $boardStore.lists;
  $: loading = $boardStore.loading;
  $: error = $boardStore.error;

  let showTask = false;
  let selectedTaskId = null;

  // Add-list inline form state
  let addingList = false;
  let newListTitle = "";
  let addListBusy = false;
  let addListError = "";

  // Add-task modal state
  let addingTask = false;
  let taskListId = null;
  let newTaskTitle = "";
  let newTaskDescription = "";
  let addTaskBusy = false;
  let addTaskError = "";

  const columnStates = {
    "Por Hacer": { status: "default", color: "default" },
    "En Progreso": { status: "active", color: "primary" },
    Bloqueado: { status: "blocked", color: "red" },
    Hecho: { status: "completed", color: "green" },
  };

  $: selectedTask =
    selectedTaskId == null
      ? null
      : lists.flatMap((l) => l.tasks || []).find((t) => t.id === selectedTaskId) ||
        null;

  async function loadBoard() {
    const me = await refreshMe();
    if (!me) {
      goto("/login");
      return;
    }
    await boardStore.loadBoard($page.params.id);
  }

  function startAddList() {
    addingList = true;
    newListTitle = "";
    addListError = "";
  }

  async function submitAddList() {
    if (!newListTitle.trim() || addListBusy) return;
    addListBusy = true;
    addListError = "";
    try {
      await boardStore.addList(newListTitle.trim());
      addingList = false;
      newListTitle = "";
    } catch (e) {
      addListError =
        e?.response?.data?.message ?? e?.message ?? "Error al crear la lista";
    } finally {
      addListBusy = false;
    }
  }

  function openAddTask(list) {
    taskListId = list.id;
    newTaskTitle = "";
    newTaskDescription = "";
    addTaskError = "";
    addingTask = true;
  }

  async function submitAddTask() {
    if (!newTaskTitle.trim() || addTaskBusy) return;
    addTaskBusy = true;
    addTaskError = "";
    try {
      await boardStore.addTask({
        listId: taskListId,
        title: newTaskTitle.trim(),
        description: newTaskDescription.trim() || null,
      });
      addingTask = false;
    } catch (e) {
      addTaskError =
        e?.response?.data?.message ?? e?.message ?? "Error al crear la tarea";
    } finally {
      addTaskBusy = false;
    }
  }

  function handleColumnConsider(e) {
    const { listId, detail } = e.detail;
    boardStore.handleConsider(listId, detail);
  }

  async function handleColumnFinalize(e) {
    const { listId, detail } = e.detail;
    try {
      await boardStore.handleFinalize(listId, detail);
    } catch (err) {
      alert(err?.message ?? "No se pudo mover la tarjeta");
    }
  }

  function handleTaskClick(e) {
    selectedTaskId = e.detail.task?.id ?? null;
    showTask = true;
  }

  function handleTaskMore(e) {
    // TODO: Show task context menu
    console.log("Task more:", e.detail.task);
  }

  function closeTask() {
    showTask = false;
    selectedTaskId = null;
  }

  onMount(loadBoard);
</script>

<svelte:head>
  <title>{board?.title || "Tablero"} - KanbanFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet"
  />
  <style>
    .custom-scrollbar::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background-color: rgba(156, 163, 175, 0.3);
      border-radius: 20px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background-color: rgba(156, 163, 175, 0.5);
    }
  </style>
</svelte:head>

<div class="flex flex-col h-full min-h-0">
  <!-- Board Header with Context -->
  <div class="px-6 py-4 shrink-0">
    <div
      class="relative rounded-xl overflow-hidden min-h-[120px] bg-surface-dark flex items-end shadow-lg group"
    >
      <div
        class="absolute inset-0 bg-gradient-to-br from-primary/80 via-primary-dark/70 to-slate-800"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-t from-background-dark via-background-dark/80 to-transparent"
      ></div>
      <div class="relative p-6 w-full flex justify-between items-end z-10">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span
              class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary text-white uppercase tracking-wider"
            >
              Kanban
            </span>
            <span class="text-slate-300 text-xs">
              {lists.length} listas
            </span>
          </div>
          <h2 class="text-white text-2xl font-bold">
            {board?.title || "Tablero"}
          </h2>
          <p class="text-slate-300 text-sm mt-1 max-w-xl">
            {board?.description || "Gestioná tus tareas en este tablero Kanban."}
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Kanban Board Area -->
  {#if loading}
    <div class="flex-1 flex items-center justify-center px-6">
      <div
        class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-6"
      >
        <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
          Cargando tablero...
        </p>
      </div>
    </div>
  {:else if error}
    <div class="flex-1 flex items-center justify-center px-6">
      <div
        class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200"
      >
        {error}
      </div>
    </div>
  {:else if lists.length === 0}
    <div class="flex-1 flex items-center justify-center px-6">
      <div class="w-full max-w-sm">
        {#if addingList}
          <div
            class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-3 shadow-sm"
          >
            <input
              type="text"
              placeholder="Introduce el nombre de la lista..."
              bind:value={newListTitle}
              on:keydown={(e) => e.key === "Enter" && submitAddList()}
              class="w-full px-3 py-2 bg-white dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
            />
            {#if addListError}
              <p class="mt-2 text-xs text-red-500">{addListError}</p>
            {/if}
            <div class="mt-2 flex items-center gap-2">
              <button
                class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
                on:click={submitAddList}
                disabled={addListBusy}
              >
                {addListBusy ? "Añadiendo..." : "Añadir lista"}
              </button>
              <button
                class="p-1.5 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark transition-colors"
                on:click={() => (addingList = false)}
                aria-label="Cancelar"
              >
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>
          </div>
        {:else}
          <div
            class="rounded-xl border border-dashed border-border-light dark:border-border-dark p-8 text-center"
          >
            <span
              class="material-symbols-outlined text-4xl text-text-sec-light dark:text-text-sec-dark"
              >view_column</span
            >
            <h3
              class="mt-3 text-lg font-semibold text-text-main-light dark:text-text-main-dark"
            >
              Este tablero aún no tiene listas
            </h3>
            <p class="mt-1 text-sm text-text-sec-light dark:text-text-sec-dark">
              Creá tu primera lista para empezar a organizar tus tareas.
            </p>
            <button
              class="mt-4 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
              on:click={startAddList}
            >
              Añadir otra lista
            </button>
          </div>
        {/if}
      </div>
    </div>
  {:else}
    <div
      class="flex-1 min-h-0 overflow-x-auto overflow-y-hidden px-6 pb-6 custom-scrollbar"
    >
      <div class="flex h-full gap-6 min-w-max pb-2">
        {#each lists as list (list.id)}
          {@const columnState = columnStates[list.title] || {
            status: "default",
            color: "default",
          }}

          <KanbanColumn
            title={list.title}
            listId={list.id}
            tasks={list.tasks || []}
            status={columnState.status}
            color={columnState.color}
            on:addTask={() => openAddTask(list)}
            on:taskClick={handleTaskClick}
            on:taskMore={handleTaskMore}
            on:consider={handleColumnConsider}
            on:finalize={handleColumnFinalize}
          />
        {/each}

        <!-- Add List Block -->
        <div class="w-80 shrink-0">
          {#if addingList}
            <div
              class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-3 shadow-sm"
            >
              <input
                type="text"
                placeholder="Introduce el nombre de la lista..."
                bind:value={newListTitle}
                on:keydown={(e) => e.key === "Enter" && submitAddList()}
                class="w-full px-3 py-2 bg-white dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
              />
              {#if addListError}
                <p class="mt-2 text-xs text-red-500">{addListError}</p>
              {/if}
              <div class="mt-2 flex items-center gap-2">
                <button
                  class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
                  on:click={submitAddList}
                  disabled={addListBusy}
                >
                  {addListBusy ? "Añadiendo..." : "Añadir lista"}
                </button>
                <button
                  class="p-1.5 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark transition-colors"
                  on:click={() => (addingList = false)}
                  aria-label="Cancelar"
                >
                  <span class="material-symbols-outlined">close</span>
                </button>
              </div>
            </div>
          {:else}
            <button
              class="w-full h-14 rounded-xl border border-dashed border-border-light dark:border-border-dark hover:border-primary dark:hover:border-primary cursor-pointer flex items-center justify-center gap-2 text-text-sec-light hover:text-primary hover:bg-slate-100 dark:hover:bg-surface-dark/50 transition-all"
              on:click={startAddList}
            >
              <span class="material-symbols-outlined">add</span>
              <span class="font-medium">Añadir otra lista</span>
            </button>
          {/if}
        </div>
      </div>
    </div>
  {/if}
</div>

<!-- Floating Action Button (Mobile) -->
<button
  class="lg:hidden fixed bottom-6 right-6 size-14 bg-primary text-white rounded-full shadow-xl flex items-center justify-center z-50 hover:bg-primary/90 transition-all"
  on:click={() => {
    if (lists.length > 0) openAddTask(lists[0]);
    else startAddList();
  }}
>
  <span class="material-symbols-outlined text-[28px]">add</span>
</button>

<!-- Add Task Modal -->
{#if addingTask}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    on:click={(e) => e.target === e.currentTarget && (addingTask = false)}
  >
    <div
      class="w-full max-w-md rounded-xl bg-surface-light dark:bg-surface-dark shadow-2xl overflow-hidden"
    >
      <div
        class="flex items-center justify-between px-5 py-4 border-b border-border-light dark:border-border-dark"
      >
        <h3
          class="text-lg font-bold text-text-main-light dark:text-text-main-dark"
        >
          Nueva tarjeta
        </h3>
        <button
          class="p-1.5 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark transition-colors"
          on:click={() => (addingTask = false)}
          aria-label="Cerrar"
        >
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="p-5 space-y-4">
        <div>
          <label
            class="text-xs font-semibold text-text-sec-light dark:text-text-sec-dark uppercase"
            >Lista</label
          >
          <select
            bind:value={taskListId}
            class="mt-1 w-full px-3 py-2 bg-white dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
          >
            {#each lists as list (list.id)}
              <option value={list.id}>{list.title}</option>
            {/each}
          </select>
        </div>
        <div>
          <label
            class="text-xs font-semibold text-text-sec-light dark:text-text-sec-dark uppercase"
            >Título</label
          >
          <input
            type="text"
            placeholder="Título de la tarjeta"
            bind:value={newTaskTitle}
            on:keydown={(e) => e.key === "Enter" && submitAddTask()}
            class="mt-1 w-full px-3 py-2 bg-white dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary"
          />
        </div>
        <div>
          <label
            class="text-xs font-semibold text-text-sec-light dark:text-text-sec-dark uppercase"
            >Descripción</label
          >
          <textarea
            placeholder="Descripción (opcional)"
            bind:value={newTaskDescription}
            rows="3"
            class="mt-1 w-full px-3 py-2 bg-white dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary resize-none"
          ></textarea>
        </div>
        {#if addTaskError}
          <p class="text-xs text-red-500">{addTaskError}</p>
        {/if}
        <div class="flex justify-end gap-2 pt-1">
          <button
            class="px-4 py-2 text-sm font-medium text-text-sec-light dark:text-text-sec-dark hover:bg-slate-100 dark:hover:bg-surface-dark rounded-lg transition-colors"
            on:click={() => (addingTask = false)}
          >
            Cancelar
          </button>
          <button
            class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
            on:click={submitAddTask}
            disabled={addTaskBusy}
          >
            {addTaskBusy ? "Añadiendo..." : "Añadir tarjeta"}
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<TaskDetails open={showTask} task={selectedTask} on:close={closeTask} />
