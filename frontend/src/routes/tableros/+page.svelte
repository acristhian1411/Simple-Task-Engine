<script>
  import { onMount } from "svelte";
  import { getBoardsWithLists } from "$lib/api/boards.js";
  import { auth, refreshMe, logout } from "$lib/stores/auth.js";
  import { goto } from "$app/navigation";

  let boards = [];
  let loading = true;
  let error = "";

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) {
        goto("/login");
        return;
      }
      const res = await getBoardsWithLists();
      boards = res?.data ?? res;
    } catch (e) {
      error =
        e?.response?.data?.message ?? e?.message ?? "Error loading boards";
    } finally {
      loading = false;
    }
  }

  onMount(load);
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
  class="font-[Inter] bg-background-light dark:bg-background-dark text-text-main-light dark:text-text-main-dark h-screen overflow-hidden flex selection:bg-primary/30"
>
  <main
    class="flex-1 flex flex-col min-w-0 bg-background-light dark:bg-background-dark relative"
  >
    <div class="flex-1 overflow-y-auto px-6 pb-6 custom-scrollbar">
      {#if loading}
        <div
          class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-6"
        >
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
            Cargando tableros...
          </p>
        </div>
      {:else if error}
        <div
          class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200"
        >
          {error}
        </div>
      {:else if boards.length === 0}
        <div
          class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-6"
        >
          <p class="text-sm text-text-sec-light dark:text-text-sec-dark">
            No hay tableros aún.
          </p>
        </div>
      {:else}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {#each boards as b (b.id)}
            <a
              href={`/tableros/${b.id}`}
              class="group rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark p-5 shadow-sm hover:shadow-md transition-shadow"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h3
                    class="text-slate-900 dark:text-white font-bold text-base truncate"
                  >
                    {b.title}
                  </h3>
                  {#if b.description}
                    <p
                      class="mt-1 text-sm text-slate-600 dark:text-[#92adc9] line-clamp-2"
                    >
                      {b.description}
                    </p>
                  {:else}
                    <p
                      class="mt-1 text-sm text-slate-500 dark:text-[#92adc9] italic"
                    >
                      Sin descripción
                    </p>
                  {/if}
                </div>
                <span
                  class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors"
                >
                  chevron_right
                </span>
              </div>

              {#if b.lists?.length}
                <div
                  class="mt-4 flex items-center gap-2 text-xs text-slate-500 dark:text-[#92adc9]"
                >
                  <span class="material-symbols-outlined text-[16px]"
                    >view_column</span
                  >
                  <span>{b.lists.length} listas</span>
                </div>
              {/if}
            </a>
          {/each}
        </div>
      {/if}
    </div>
  </main>
</div>
