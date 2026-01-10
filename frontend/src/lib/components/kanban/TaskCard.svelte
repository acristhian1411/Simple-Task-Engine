<script>
  import { createEventDispatcher } from "svelte";

  export let task = {};

  const dispatch = createEventDispatcher();

  function getCardClasses() {
    const base =
      "group p-4 rounded-lg shadow border cursor-pointer transition-all hover:shadow-md text-slate-900 dark:text-slate-200";
    const statusClasses = {
      default:
        "bg-white dark:bg-[#0f1724] border-slate-200 dark:border-slate-700/40 hover:border-primary/50 dark:hover:border-primary/50",
      active:
        "bg-white dark:bg-[#0f1724] border-slate-200 dark:border-slate-700/40 hover:border-primary/50 dark:hover:border-primary/50 ring-1 ring-primary/20 dark:ring-primary/10",
      blocked:
        "bg-red-50 dark:bg-red-900/10 border-l-4 border-l-red-500 border-y border-r border-slate-200 dark:border-slate-700/50 hover:border-r-red-500 text-slate-900 dark:text-red-200",
      completed:
        "bg-slate-50 dark:bg-[#0b1220] border border-slate-200 dark:border-slate-700/40 cursor-pointer transition-all opacity-80 hover:opacity-100 text-slate-700 dark:text-slate-400",
    };
    return `${base} ${statusClasses[task.status || "default"]}`;
  }

  function getTagColor(tag) {
    const colors = {
      DISEÑO: "purple",
      DEV: "blue",
      BACKEND: "blue",
      FRONTEND: "blue",
      QA: "green",
      COPY: "yellow",
      CONTENT: "slate",
      DEVOPS: "teal",
      ARCH: "blue",
    };
    const color = colors[tag?.toUpperCase()] || "slate";
    return {
      bg: `bg-${color}-100 dark:bg-${color}-900/30`,
      text: `text-${color}-600 dark:text-${color}-300`,
    };
  }

  function getPriorityBadge(priority) {
    if (!priority || priority.toLowerCase() === "normal") return null;
    const color = priority.toLowerCase() === "alta" ? "red" : "orange";
    return {
      bg: `bg-${color}-100 dark:bg-${color}-900/30`,
      text: `text-${color}-600 dark:text-${color}-300`,
    };
  }

  function getAvatarInitials(name) {
    if (!name) return "";
    const parts = name.trim().split(" ");
    if (parts.length >= 2) {
      return parts[0][0] + parts[parts.length - 1][0];
    }
    return parts[0].substring(0, 2).toUpperCase();
  }

  function getAvatarColor(name) {
    const colors = [
      "pink",
      "indigo",
      "orange",
      "teal",
      "blue",
      "green",
      "purple",
      "yellow",
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
  }

  function formatDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleDateString("es-AR", { month: "short", day: "numeric" });
  }

  $: tagColors = task.tags?.[0] ? getTagColor(task.tags[0]) : null;
  $: priorityBadge = getPriorityBadge(task.priority);
  $: avatarInitials = task.assignee
    ? getAvatarInitials(task.assignee.name || task.assignee.email)
    : "";
  $: avatarColor = task.assignee
    ? getAvatarColor(task.assignee.name || task.assignee.email)
    : "slate";
</script>

<!-- svelte-ignore a11y_click_events_have_key_events -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
<div class={getCardClasses()} on:click={() => dispatch("click", { task })}>
  <!-- Task Header -->
  <div class="flex justify-between items-start mb-2">
    <div class="flex gap-2">
      {#if tagColors}
        <span
          class={`${tagColors.bg} ${tagColors.text} text-[10px] font-bold px-2 py-0.5 rounded`}
        >
          {task.tags[0]}
        </span>
      {/if}
      {#if priorityBadge}
        <span
          class={`${priorityBadge.bg} ${priorityBadge.text} text-[10px] font-bold px-2 py-0.5 rounded flex items-center gap-1`}
        >
          <span class="material-symbols-outlined text-[10px]">flag</span>
          {task.priority}
        </span>
      {/if}
    </div>
    <button
      class="text-slate-400 text-[16px]"
      on:click|stopPropagation={() => dispatch("more", { task })}
    >
      <span class="material-symbols-outlined">more_horiz</span>
    </button>
  </div>

  <!-- Task Title -->
  <h4
    class="text-slate-800 dark:text-white font-medium text-sm mb-3 leading-snug {task.status ===
    'completed'
      ? 'line-through text-slate-600 dark:text-slate-300'
      : ''}"
  >
    {task.title}
  </h4>

  <!-- Task Cover Image (if exists) -->
  {#if task.coverImage}
    <div
      class="w-full h-24 rounded-md mb-3 bg-cover bg-center"
      style="background-image: url('{task.coverImage}')"
      data-alt="Task cover image"
    ></div>
  {/if}

  <!-- Progress Bar (if subtasks exist) -->
  {#if task.subtasks && task.subtasks.length > 0}
    <div
      class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full mb-3 overflow-hidden"
    >
      <div
        class="h-full bg-primary rounded-full transition-all"
        style="width: {Math.round(
          (task.subtasks.filter((st) => st.completed).length /
            task.subtasks.length) *
            100
        )}%"
      ></div>
    </div>
  {/if}

  <!-- Task Footer -->
  <div
    class="flex items-center justify-between {task.coverImage
      ? 'pt-1'
      : 'mt-4 pt-3'} border-t border-slate-100 dark:border-slate-700/50"
  >
    <div class="flex items-center gap-3 text-slate-400 dark:text-slate-400">
      <!-- Blockers Indicator -->
      {#if task.blockers && task.blockers.length > 0}
        <div
          class="flex items-center gap-1"
          title={`${task.blockers.length} Blocker${task.blockers.length > 1 ? "s" : ""}`}
        >
          <span class="material-symbols-outlined text-[16px] text-red-400"
            >link_off</span
          >
        </div>
      {/if}

      <!-- Dependencies Ready -->
      {#if task.dependenciesReady}
        <div class="flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px] text-green-500"
            >link</span
          >
          <span class="text-green-500 text-[10px] font-bold">READY</span>
        </div>
      {/if}

      <!-- Subtasks Progress -->
      {#if task.subtasks && task.subtasks.length > 0}
        <div class="flex items-center gap-1 text-xs">
          <span class="material-symbols-outlined text-[16px]">check_box</span>
          {task.subtasks.filter((st) => st.completed).length}/{task.subtasks
            .length}
        </div>
      {/if}

      <!-- Due Date -->
      {#if task.dueDate}
        <div
          class="flex items-center gap-1 text-xs {task.isOverdue
            ? 'text-red-500 font-medium'
            : ''}"
        >
          <span class="material-symbols-outlined text-[16px]"
            >{task.isOverdue ? "event_busy" : "calendar_today"}</span
          >
          {formatDate(task.dueDate)}
          {#if task.isOverdue}
            (Vencida)
          {/if}
        </div>
      {/if}

      <!-- Completed Status -->
      {#if task.status === "completed"}
        <div
          class="flex items-center gap-1 text-xs font-bold text-green-600 dark:text-green-500"
        >
          <span class="material-symbols-outlined text-[16px]">check_circle</span
          >
          Completado
        </div>
      {/if}
    </div>

    <!-- Assignee Avatar -->
    <div class="flex items-center gap-2">
      {#if task.assignees && task.assignees.length > 1}
        <!-- Multiple assignees -->
        <div class="flex -space-x-2">
          {#each task.assignees.slice(0, 2) as assignee}
            {#if assignee.avatar}
              <div
                class="size-6 rounded-full bg-cover bg-center ring-2 ring-white dark:ring-[#1e293b]"
                data-alt="Avatar {assignee.name}"
                style="background-image: url('{assignee.avatar}')"
              ></div>
            {:else}
              <div
                class="size-6 rounded-full bg-{getAvatarColor(
                  assignee.name || assignee.email
                )}-500 flex items-center justify-center text-[10px] text-white font-bold ring-2 ring-white dark:ring-[#1e293b]"
              >
                {getAvatarInitials(assignee.name || assignee.email)}
              </div>
            {/if}
          {/each}
          {#if task.assignees.length > 2}
            <div
              class="size-6 rounded-full bg-slate-700 flex items-center justify-center text-xs text-white font-medium ring-2 ring-white dark:ring-[#1e293b]"
            >
              +{task.assignees.length - 2}
            </div>
          {/if}
        </div>
      {:else if task.assignee || (task.assignees && task.assignees.length === 1)}
        <!-- Single assignee -->
        {#if task.assignee ? task.assignee.avatar : task.assignees[0]?.avatar}
          <div
            class="size-6 rounded-full bg-cover bg-center {task.status ===
            'completed'
              ? 'grayscale'
              : ''}"
            data-alt="User avatar {task.assignee
              ? task.assignee.name
              : task.assignees[0].name}"
            style="background-image: url('{task.assignee
              ? task.assignee.avatar
              : task.assignees[0].avatar}')"
          ></div>
        {:else}
          <div
            class="size-6 rounded-full bg-{avatarColor}-600 flex items-center justify-center text-[10px] text-white font-bold {task.status ===
            'completed'
              ? 'grayscale'
              : ''}"
          >
            {avatarInitials}
          </div>
        {/if}
      {/if}
    </div>
  </div>

  <!-- Blocker Reason (if blocked) -->
  {#if task.blockerReason}
    <div
      class="p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-100 dark:border-red-900/30 mb-3"
    >
      <p
        class="text-red-600 dark:text-red-300 text-xs font-medium flex items-center gap-1"
      >
        <span class="material-symbols-outlined text-[14px]">block</span>
        {task.blockerReason}
      </p>
    </div>
  {/if}
</div>
