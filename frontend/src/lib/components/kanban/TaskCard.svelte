<script>
  import { createEventDispatcher } from "svelte";

  export let task = {};

  const dispatch = createEventDispatcher();

  function getCardClasses() {
    const base =
      "group p-4 rounded-lg shadow border cursor-pointer transition-all hover:shadow-md text-slate-900 dark:text-slate-200";
    const statusClasses = {
      default:
        "bg-white dark:bg-surface-dark border-slate-200 dark:border-border-dark/40 hover:border-primary/50 dark:hover:border-primary/50",
      active:
        "bg-white dark:bg-surface-dark border-slate-200 dark:border-border-dark/40 hover:border-primary/50 dark:hover:border-primary/50 ring-1 ring-primary/20 dark:ring-primary/10",
      blocked:
        "bg-red-50 dark:bg-red-900/10 border-l-4 border-l-red-500 border-y border-r border-slate-200 dark:border-border-dark/50 hover:border-r-red-500 text-slate-900 dark:text-red-200",
      completed:
        "bg-slate-50 dark:bg-surface-dark border border-slate-200 dark:border-border-dark/40 cursor-pointer transition-all opacity-80 hover:opacity-100 text-slate-700 dark:text-slate-400",
    };
    return `${base} ${statusClasses[task.status || "default"]}`;
  }

  function getTagColor(tag) {
    const colors = {
      DISEÑO: {
        bg: "bg-purple-100 dark:bg-purple-900/30",
        text: "text-purple-600 dark:text-purple-300",
      },
      DEV: {
        bg: "bg-blue-100 dark:bg-blue-900/30",
        text: "text-blue-600 dark:text-blue-300",
      },
      BACKEND: {
        bg: "bg-blue-100 dark:bg-blue-900/30",
        text: "text-blue-600 dark:text-blue-300",
      },
      FRONTEND: {
        bg: "bg-blue-100 dark:bg-blue-900/30",
        text: "text-blue-600 dark:text-blue-300",
      },
      QA: {
        bg: "bg-green-100 dark:bg-green-900/30",
        text: "text-green-600 dark:text-green-300",
      },
      COPY: {
        bg: "bg-yellow-100 dark:bg-yellow-900/30",
        text: "text-yellow-600 dark:text-yellow-300",
      },
      CONTENT: {
        bg: "bg-slate-100 dark:bg-slate-800",
        text: "text-slate-600 dark:text-slate-300",
      },
      DEVOPS: {
        bg: "bg-teal-100 dark:bg-teal-900/30",
        text: "text-teal-600 dark:text-teal-300",
      },
      ARCH: {
        bg: "bg-blue-100 dark:bg-blue-900/30",
        text: "text-blue-600 dark:text-blue-300",
      },
    };
    return (
      colors[tag?.toUpperCase()] || {
        bg: "bg-slate-100 dark:bg-slate-800",
        text: "text-slate-600 dark:text-slate-300",
      }
    );
  }

  function getPriorityBadge(priority) {
    if (!priority || priority.toLowerCase() === "normal") return null;
    const color = priority.toLowerCase() === "alta" ? "red" : "orange";
    return {
      bg:
        color === "red"
          ? "bg-red-100 dark:bg-red-900/30"
          : "bg-orange-100 dark:bg-orange-900/30",
      text:
        color === "red"
          ? "text-red-600 dark:text-red-300"
          : "text-orange-600 dark:text-orange-300",
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

  const avatarColorClasses = {
    pink: "bg-pink-500",
    indigo: "bg-indigo-500",
    orange: "bg-orange-500",
    teal: "bg-teal-500",
    blue: "bg-blue-500",
    green: "bg-green-500",
    purple: "bg-purple-500",
    yellow: "bg-yellow-500",
    slate: "bg-slate-500",
  };

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
          (task.subtasks.filter((st) => st.is_completed).length /
            task.subtasks.length) *
            100,
        )}%"
      ></div>
    </div>
  {/if}

  <!-- Component & Bug Badges -->
  {#if (task.components && task.components.length) || (task.bugs && task.bugs.length)}
    <div class="flex flex-wrap items-center gap-1.5 mb-3">
      {#if task.components && task.components.length}
        {#each task.components as comp (comp.id)}
          <span
            class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300"
            title={comp.name || `#${comp.id}`}
          >
            <span class="material-symbols-outlined text-[11px]">widgets</span
            >{comp.name || `#${comp.id}`}
          </span>
        {/each}
      {/if}
      {#if task.bugs && task.bugs.length}
        {#each task.bugs.slice(0, 2) as bug (bug.id)}
          <span
            class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-300"
            title={bug.title || `Bug #${bug.id}`}
          >
            <span class="material-symbols-outlined text-[11px]">bug_report</span
            >{bug.title || `#${bug.id}`}
          </span>
        {/each}
        {#if task.bugs.length > 2}
          <span
            class="inline-flex items-center text-[10px] font-medium px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-300"
          >
            +{task.bugs.length - 2}
          </span>
        {/if}
      {/if}
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
          {task.subtasks.filter((st) => st.is_completed).length}/{task.subtasks
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
                  assignee.name || assignee.email,
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
            class="size-6 rounded-full {avatarColorClasses[avatarColor] ||
              'bg-slate-500'} flex items-center justify-center text-[10px] text-white font-bold {task.status ===
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
