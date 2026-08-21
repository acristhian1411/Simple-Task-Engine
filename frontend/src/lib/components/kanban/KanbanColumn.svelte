<script>
  import { createEventDispatcher } from "svelte";
  import { dndzone } from "svelte-dnd-action";
  import TaskCard from "./TaskCard.svelte";

  export let title = "";
  export let tasks = [];
  export let listId = null;
  export let status = "default"; // default, active, blocked, completed
  export let color = "default"; // default, primary, red, green

  const dispatch = createEventDispatcher();

  // Array local para el dndzone: evita mutar el array del store y permite sync controlado
  let localTasks = [];
  let dragging = false;

  // Sincroniza desde la prop SOLO cuando no hay drag en curso
  $: if (!dragging) {
    localTasks = tasks;
  }

  function getHeaderClasses() {
    const base =
      "p-4 flex items-center justify-between border-b backdrop-blur-sm rounded-t-xl sticky top-0 z-10";
    const statusClasses = {
      default:
        "border-slate-200 dark:border-border-dark/50 bg-white/60 dark:bg-surface-dark",
      active:
        "border-slate-200 dark:border-border-dark/50 bg-white/60 dark:bg-surface-dark ring-1 ring-primary/20 dark:ring-primary/10",
      blocked:
        "border-red-100 dark:border-red-900/20 bg-red-50 dark:bg-red-950/5",
      completed:
        "border-slate-200 dark:border-border-dark/50 bg-white/60 dark:bg-surface-dark",
    };
    return `${base} ${statusClasses[status]}`;
  }

  function getColumnClasses() {
    const base = "w-80 shrink-0 flex flex-col h-full rounded-xl shadow-sm";
    const statusClasses = {
      default:
        "bg-slate-50 dark:bg-surface-dark border border-slate-200 dark:border-border-dark/50",
      active:
        "bg-slate-100 dark:bg-surface-dark border-t-4 border-t-primary border-x border-b border-x-slate-200 dark:border-x-border-dark/50 border-b-slate-200 dark:border-b-border-dark/50",
      blocked:
        "bg-red-50 dark:bg-red-950/10 border-t-4 border-t-red-500 border-x border-b border-x-red-100 dark:border-x-red-900/20 border-b-red-100 dark:border-b-red-900/20",
      completed:
        "bg-slate-100 dark:bg-surface-dark border border-slate-200 dark:border-border-dark/50 opacity-80 hover:opacity-100 transition-opacity",
    };
    return `${base} ${statusClasses[status]}`;
  }

  function getBadgeClasses() {
    const base = "text-xs px-2 py-0.5 rounded-full font-bold";
    const colorClasses = {
      default:
        "bg-slate-200 dark:bg-surface-dark text-slate-600 dark:text-slate-300",
      primary: "bg-primary/20 text-primary dark:text-blue-300",
      red: "bg-red-200 dark:bg-red-900/40 text-red-800 dark:text-red-200",
      green:
        "bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400",
    };
    return `${base} ${colorClasses[color]}`;
  }

  function getIconClasses() {
    const base = "text-slate-400 hover:text-primary transition-colors";
    const statusClasses = {
      default: base,
      active: base,
      blocked: "text-red-300 hover:text-red-500 transition-colors",
      completed: "text-slate-400 hover:text-green-500 transition-colors",
    };
    return statusClasses[status];
  }

  function getIcon() {
    switch (status) {
      case "blocked":
        return "warning";
      case "completed":
        return "check_circle";
      default:
        return "add";
    }
  }

  function getTitleClasses() {
    const base = "font-bold text-sm";
    const statusClasses = {
      default: "text-slate-900 dark:text-white",
      active: "text-slate-900 dark:text-white",
      blocked: "text-red-900 dark:text-red-200",
      completed: "text-slate-900 dark:text-white",
    };
    return `${base} ${statusClasses[status]}`;
  }

  function handleConsider(e) {
    dragging = true;
    localTasks = e.detail.items;
    dispatch("consider", { listId, detail: e.detail });
  }

  function handleFinalize(e) {
    localTasks = e.detail.items;
    dragging = false;
    dispatch("finalize", { listId, detail: e.detail });
  }
</script>

<div class={getColumnClasses()}>
  <!-- Column Header -->
  <div class={getHeaderClasses()}>
    <div class="flex items-center gap-2">
      <h3 class={getTitleClasses()}>{title}</h3>
      <span class={getBadgeClasses()}>{localTasks.length}</span>
    </div>
    <button
      class={getIconClasses()}
      on:click={() => dispatch("addTask")}
      aria-label="Agregar tarjeta"
    >
      <span class="material-symbols-outlined text-[20px]">{getIcon()}</span>
    </button>
  </div>

  <!-- Cards Container -->
  <div
    class="flex-1 overflow-y-auto p-3 flex flex-col gap-3 custom-scrollbar"
    use:dndzone={{ items: localTasks, type: "board" }}
    on:consider={handleConsider}
    on:finalize={handleFinalize}
  >
    {#each localTasks as task (task.id)}
      <TaskCard
        {task}
        on:click={() => dispatch("taskClick", { task })}
        on:more={() => dispatch("taskMore", { task })}
      />
    {/each}
  </div>

  <!-- Add Task Button -->
  <button
    class="m-2 p-2 flex items-center justify-center gap-2 text-slate-500 hover:bg-slate-200 dark:hover:bg-[#233648] hover:text-slate-700 dark:hover:text-white rounded-lg transition-colors text-sm font-medium"
    on:click={() => dispatch("addTask")}
  >
    <span class="material-symbols-outlined text-[20px]">add</span>
    Añadir Tarjeta
  </button>
</div>
