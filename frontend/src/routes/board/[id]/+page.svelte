<script>
  import { onMount } from "svelte";
  import { page } from "$app/stores";
  import { goto } from "$app/navigation";
  import { refreshMe } from "$lib/stores/auth.js";
  import { getBoard } from "$lib/api/boards.js";
  import { getListsWithTasks } from "$lib/api/lists.js";
  import KanbanColumn from "$lib/components/kanban/KanbanColumn.svelte";
  import TaskDetails from "$lib/components/TaskDetails.svelte";

  let board = null;
  let lists = [];
  let tasks = [];
  let loading = true;
  let error = "";
  let showTask = false;
  let selectedTask = null;

  // Column states based on the template
  const columnStates = {
    "Por Hacer": { status: "default", color: "default" },
    "En Progreso": { status: "active", color: "primary" },
    Bloqueado: { status: "blocked", color: "red" },
    Hecho: { status: "completed", color: "green" },
  };

  async function loadBoard() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) {
        goto("/login");
        return;
      }

      const boardId = $page.params.id;
      const boardsRes = await getBoard(boardId);
      board = boardsRes?.data ?? boardsRes;

      if (!board) {
        error = "Tablero no encontrado";
        return;
      }

      const listsRes = await getListsWithTasks({ board_id: boardId });
      lists = listsRes?.data ?? listsRes ?? [];

      // Aggregate tasks for fallback mapping
      tasks = lists.flatMap((l) => l.tasks || []);
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando tablero";
    } finally {
      loading = false;
    }
  }

  function getTasksForList(listTitle) {
    if (lists && lists.length > 0) {
      const listObj = lists.find((l) => l.title === listTitle);
      if (listObj) return listObj.tasks || [];
    }

    return tasks.filter((task) => {
      const list = lists.find((l) => l.id === task.list_id);
      if (list && list.title === listTitle) return true;

      if (listTitle === "Por Hacer" && (!task.status || task.status === "todo"))
        return true;
      if (listTitle === "En Progreso" && task.status === "in_progress")
        return true;
      if (listTitle === "Bloqueado" && task.status === "blocked") return true;
      if (listTitle === "Hecho" && task.status === "done") return true;

      return false;
    });
  }

  function handleTaskClick(event) {
    const { task } = event.detail;
    selectedTask = task;
    showTask = true;
  }

  function handleTaskMore(event) {
    const { task } = event.detail;
    // TODO: Show task context menu
    console.log("Task more:", task);
  }

  function handleAddTask(columnTitle) {
    // TODO: Open add task modal with column pre-selected
    console.log("Add task to column:", columnTitle);
  }

  function closeTask() {
    showTask = false;
    selectedTask = null;
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
              {lists.length} listas · {tasks.length} tareas
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
  {:else}
    <div class="flex-1 min-h-0 overflow-x-auto overflow-y-hidden px-6 pb-6 custom-scrollbar">
      <div class="flex h-full gap-6 min-w-max pb-2">
        {#each lists.length > 0 ? lists : ["Por Hacer", "En Progreso", "Bloqueado", "Hecho"] as column (column.id || column)}
          {@const columnTasks = getTasksForList(column.title || column)}
          {@const columnState = columnStates[column.title || column] || {
            status: "default",
            color: "default",
          }}

          <KanbanColumn
            title={column.title || column}
            tasks={columnTasks}
            status={columnState.status}
            color={columnState.color}
            on:addTask={() => handleAddTask(column.title || column)}
            on:taskClick={handleTaskClick}
            on:taskMore={handleTaskMore}
          />
        {/each}

        <!-- Add List Button -->
        <div
          class="w-80 shrink-0 h-14 rounded-xl border border-dashed border-border-light dark:border-border-dark hover:border-primary dark:hover:border-primary cursor-pointer flex items-center justify-center gap-2 text-text-sec-light hover:text-primary hover:bg-slate-100 dark:hover:bg-surface-dark/50 transition-all"
        >
          <span class="material-symbols-outlined">add</span>
          <span class="font-medium">Añadir otra lista</span>
        </div>
      </div>
    </div>
  {/if}
</div>

<!-- Floating Action Button (Mobile) -->
<button
  class="lg:hidden fixed bottom-6 right-6 size-14 bg-primary text-white rounded-full shadow-xl flex items-center justify-center z-50 hover:bg-primary/90 transition-all"
  on:click={() => handleAddTask("Por Hacer")}
>
  <span class="material-symbols-outlined text-[28px]">add</span>
</button>

<TaskDetails open={showTask} task={selectedTask} on:close={closeTask} />