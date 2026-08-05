<script>
  import { onMount } from "svelte";
  import { page } from "$app/stores";
  import { goto } from "$app/navigation";
  import { auth, refreshMe, logout } from "$lib/stores/auth.js";
  import { getBoards } from "$lib/api/boards.js";
  import { getListsWithTasks } from "$lib/api/lists.js";
  import KanbanColumn from "$lib/components/kanban/KanbanColumn.svelte";
  import TaskDetails from "$lib/components/TaskDetails.svelte";

  let board = null;
  let lists = [];
  let tasks = [];
  let loading = true;
  let error = "";

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
      // Get board details (basic) and lists with tasks for this board
      const boardsRes = await getBoards();
      const allBoards = boardsRes?.data ?? boardsRes;
      board = allBoards.find((b) => b.id == boardId);

      if (!board) {
        error = "Tablero no encontrado";
        return;
      }

      const listsRes = await getListsWithTasks({ board_id: boardId });
      // listsRes is a pagination object; its `data` key contains the arrays of lists
      lists = listsRes?.data ?? listsRes ?? [];

      // Aggregate tasks for fallback mapping
      tasks = lists.flatMap((l) => l.tasks || []);
    } catch (e) {
      error = e?.response?.data?.message ?? e?.message ?? "Error loading board";
    } finally {
      loading = false;
    }
  }

  function getTasksForList(listTitle) {
    // If lists are objects returned from API, try to find the list by title
    if (lists && lists.length > 0) {
      const listObj = lists.find((l) => l.title === listTitle);
      if (listObj) return listObj.tasks || [];
    }

    // Fallback: Map tasks to lists based on their status or list_id
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

  let showTask = false;
  let selectedTask = null;

  function closeTask() {
    showTask = false;
    selectedTask = null;
  }

  onMount(loadBoard);
</script>

<svelte:head>
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

<div
  class="font-[Inter] bg-background-light dark:bg-background-dark text-slate-900 dark:text-white h-screen overflow-hidden flex selection:bg-primary/30"
>
  <!-- Main Content -->
  <main
    class="flex-1 flex flex-col min-w-0 bg-background-light dark:bg-background-dark relative"
  >
    <!-- Board Header with Context -->
    <div class="px-6 py-4 shrink-0">
      <div
        class="relative rounded-xl overflow-hidden min-h-[120px] bg-surface-dark flex items-end shadow-lg group"
      >
        <div
          class="absolute inset-0 bg-cover bg-center opacity-40 group-hover:opacity-50 transition-opacity"
          data-alt="Abstract gradient mesh background in blue and purple tones"
          style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAZqPKff11GqU88aIJFjKhJZHRMK5srxVOFaOO3xh1Ak6Biak0ugPlx5IiP2aoGEsvitzGrYMGxWIVqVA7t0_p6evBPI00oh9tGnjH6Sj39mGm9wMNvEpX8h7LRaFLCJnW-j0NdF32uJulaMXQA5qjcfFQvi9R2uPk_U5bh8ma2DK0NynWd0d7VQ5oMa_eV2AuSG8yYW4b9SWaqZMqrrNxgU1hqHoUJ0hnENReN23i_jq-YY8YJWlTcS55trE6AZNUZZ4y37fNad80');"
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
                En Curso
              </span>
              <span class="text-slate-300 text-xs">Oct 12 - Oct 26</span>
            </div>
            <h2 class="text-white text-2xl font-bold">
              {board?.title || "Tablero"}
            </h2>
            <p class="text-slate-300 text-sm mt-1 max-w-xl">
              {board?.description ||
                "Gestioná tus tareas en este tablero Kanban."}
            </p>
          </div>
          <!-- <div class="hidden sm:flex -space-x-2">
             Team members avatars (placeholder) 
            <div
              class="size-8 rounded-full ring-2 ring-background-dark bg-cover bg-center"
              data-alt="Team member avatar 1"
            ></div>
            <div
              class="size-8 rounded-full ring-2 ring-background-dark bg-cover bg-center"
              data-alt="Team member avatar 2"
            ></div>
            <div
              class="size-8 rounded-full ring-2 ring-background-dark bg-cover bg-center"
              data-alt="Team member avatar 3"
            ></div>
            <div
              class="size-8 rounded-full ring-2 ring-background-dark bg-slate-700 flex items-center justify-center text-xs text-white font-medium"
            >
              +4
            </div>
          </div> -->
        </div>
      </div>
    </div>

    <!-- Kanban Board Area -->
    {#if loading}
      <div class="flex-1 flex items-center justify-center">
        <div
          class="rounded-xl border border-slate-200 dark:border-surface-border bg-white dark:bg-surface-dark p-6"
        >
          <p class="text-sm text-slate-600 dark:text-[#92adc9]">
            Cargando tablero...
          </p>
        </div>
      </div>
    {:else if error}
      <div class="flex-1 flex items-center justify-center">
        <div
          class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200"
        >
          {error}
        </div>
      </div>
    {:else}
      <div
        class="flex-1 overflow-x-auto overflow-y-hidden px-6 pb-6 custom-scrollbar"
      >
        <div class="flex h-full gap-6 min-w-max pb-2">
          <!-- Render columns based on available lists or default columns -->
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
            class="w-80 shrink-0 h-14 rounded-xl border border-dashed border-slate-300 dark:border-surface-border hover:border-primary dark:hover:border-primary cursor-pointer flex items-center justify-center gap-2 text-slate-500 hover:text-primary hover:bg-slate-100 dark:hover:bg-surface-dark/50 transition-all"
          >
            <span class="material-symbols-outlined">add</span>
            <span class="font-medium">Añadir otra lista</span>
          </div>
        </div>
      </div>
    {/if}
  </main>
</div>

<!-- Floating Action Button (Mobile) -->
<button
  class="lg:hidden fixed bottom-6 right-6 size-14 bg-primary text-white rounded-full shadow-xl flex items-center justify-center z-50 hover:bg-primary/90 transition-all"
>
  <span class="material-symbols-outlined text-[28px]">add</span>
</button>

<TaskDetails open={showTask} task={selectedTask} on:close={closeTask} />
