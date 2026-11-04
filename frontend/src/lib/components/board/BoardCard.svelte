<script>
  import { deleteBoard } from "$lib/api/boards.js";

  export let board = {};
  export let onClick = null;
  export let onDelete = null;

  let menuOpen = false;
  let deleting = false;

  // Calculate progress based on lists and tasks if available
  function calculateProgress(board) {
    if (!board.lists || board.lists.length === 0) return 0;

    let totalTasks = 0;
    let completedTasks = 0;

    board.lists.forEach((list) => {
      if (list.tasks) {
        totalTasks += list.tasks.length;
        completedTasks += list.tasks.filter(
          (task) => task.status === "done",
        ).length;
      }
    });

    return totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
  }

  // Miembros reales del tablero (dueño + invitados)
  function getInitials(name = "") {
    const parts = name.trim().split(" ");
    if (parts.length >= 2) {
      return parts[0][0] + parts[parts.length - 1][0];
    }
    return (parts[0] || "").substring(0, 2).toUpperCase();
  }

  // Get category color based on board title or category
  function getCategoryColor(board) {
    const colors = [
      {
        bg: "bg-blue-100 dark:bg-blue-900/30",
        text: "text-blue-800 dark:text-blue-300",
        progress: "bg-primary",
      },
      {
        bg: "bg-orange-100 dark:bg-orange-900/30",
        text: "text-orange-800 dark:text-orange-300",
        progress: "bg-orange-500",
      },
      {
        bg: "bg-purple-100 dark:bg-purple-900/30",
        text: "text-purple-800 dark:text-purple-300",
        progress: "bg-purple-500",
      },
      {
        bg: "bg-emerald-100 dark:bg-emerald-900/30",
        text: "text-emerald-800 dark:text-emerald-300",
        progress: "bg-emerald-500",
      },
    ];

    // Simple hash to consistently assign colors based on board id
    const index = board.id ? board.id % colors.length : 0;
    return colors[index];
  }

  // Get category name (placeholder)
  function getCategoryName(board) {
    const categories = ["Desarrollo", "Marketing", "RRHH", "Finanzas"];
    const index = board.id ? board.id % categories.length : 0;
    return categories[index];
  }

  // Format relative time
  function getRelativeTime(board) {
    if (!board.updated_at) return "Hoy";

    const now = new Date();
    const updated = new Date(board.updated_at);
    const diffTime = Math.abs(now - updated);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 1) return "Hoy";
    if (diffDays <= 7) return `${diffDays} días`;
    if (diffDays <= 30) return `${Math.ceil(diffDays / 7)} sem`;
    return `${Math.ceil(diffDays / 30)} mes`;
  }

  $: progress = calculateProgress(board);
  $: teamMembers = [board.owner, ...(board.members || [])].filter(Boolean);
  $: categoryColor = getCategoryColor(board);
  $: categoryName = getCategoryName(board);
  $: relativeTime = getRelativeTime(board);

  function handleClick() {
    if (onClick) {
      onClick(board);
    }
  }

  function toggleMenu() {
    menuOpen = !menuOpen;
  }

  async function handleDelete() {
    menuOpen = false;
    const ok = confirm(
      `¿Eliminar el tablero "${board.title}"? Dejará de verse junto con sus listas y tareas.`,
    );
    if (!ok) return;

    deleting = true;
    try {
      await deleteBoard(board.id);
      onDelete?.(board);
    } catch (e) {
      alert(
        e?.response?.data?.error ?? e?.message ?? "No se pudo eliminar el tablero",
      );
    } finally {
      deleting = false;
    }
  }
</script>

<!-- Cierra el menú al hacer clic en cualquier otro lado -->
<svelte:window on:click={() => (menuOpen = false)} />

<div
  class="board-card group {deleting ? 'opacity-50 pointer-events-none' : ''}"
  on:click={handleClick}
  on:keydown={(e) => e.key === "Enter" && handleClick()}
  role="button"
  tabindex="0"
>
  <!-- Cover Image -->
  <div
    class="h-24 w-full bg-gradient-to-r from-primary to-primary-dark relative"
  >
    <div
      class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"
    ></div>
  </div>

  <!-- Card Content -->
  <div class="p-4 flex flex-col flex-1">
    <!-- Category and Menu -->
    <div class="flex justify-between items-start mb-2">
      <div class="relative">
        <button
          class="cursor-pointer text-text-sec-light dark:text-text-sec-dark hover:text-white focus:opacity-100 group-hover:opacity-100 transition-opacity {menuOpen
            ? 'opacity-100'
            : 'opacity-0'}"
          on:click|stopPropagation={toggleMenu}
          on:keydown|stopPropagation
          type="button"
          aria-label="Opciones del tablero"
          aria-haspopup="menu"
          aria-expanded={menuOpen}
        >
          <span class="material-symbols-outlined text-[20px]">more_horiz</span>
        </button>

        {#if menuOpen}
          <div
            class="absolute left-0 top-full mt-1 z-20 min-w-40 rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark shadow-lg py-1"
            role="menu"
          >
            <button
              class="w-full cursor-pointer flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              on:click|stopPropagation={handleDelete}
              on:keydown|stopPropagation
              type="button"
              role="menuitem"
            >
              <span class="material-symbols-outlined text-[18px]">delete</span>
              Eliminar tablero
            </button>
          </div>
        {/if}
      </div>
    </div>

    <!-- Title and Description -->
    <h3
      class="text-text-main-light dark:text-text-main-dark font-bold text-lg mb-1 group-hover:text-primary transition-colors cursor-pointer"
    >
      {board.title || "Untitled Board"}
    </h3>
    <p
      class="text-text-sec-light dark:text-text-sec-dark text-sm line-clamp-2 mb-4"
    >
      {board.description || "No description available"}
    </p>

    <!-- Progress Section -->
    <div class="mt-auto flex flex-col gap-3">
      <div class="flex flex-col gap-1">
        <div
          class="flex justify-between text-xs text-text-sec-light dark:text-text-sec-dark"
        >
          <span>Progreso</span>
          <span>{progress}%</span>
        </div>
        <div class="progress-bar">
          <div
            class="progress-fill {categoryColor.progress}"
            style="width: {progress}%"
          ></div>
        </div>
      </div>

      <!-- Meta Information -->
      <div
        class="flex items-center justify-between border-t border-border-light dark:border-border-dark pt-3 mt-1"
      >
        <!-- Team Members -->
        <div class="avatar-group">
          {#each teamMembers.slice(0, 2) as member (member.id)}
            <div
              class="avatar avatar-sm ring-2 ring-white dark:ring-surface-dark bg-indigo-500 text-white flex items-center justify-center text-[10px] font-bold"
              title={member.name || member.email}
            >
              {getInitials(member.name || member.email)}
            </div>
          {/each}
          {#if teamMembers.length > 2}
            <div
              class="flex items-center justify-center avatar avatar-sm ring-2 ring-white dark:ring-surface-dark bg-slate-100 dark:bg-surface-dark text-[10px] font-medium text-text-sec-light dark:text-text-sec-dark"
            >
              +{teamMembers.length - 2}
            </div>
          {/if}
        </div>

        <!-- Time -->
        <div
          class="flex items-center gap-1 text-text-sec-light dark:text-text-sec-dark text-xs"
        >
          <span class="material-symbols-outlined text-[14px]"
            >calendar_today</span
          >
          <span>{relativeTime}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>
