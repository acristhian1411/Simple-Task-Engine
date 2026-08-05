<script>
  import Modal from "$lib/components/ui/Modal.svelte";
  import { createEventDispatcher } from "svelte";

  export let open = false;
  export let task = null;

  const dispatch = createEventDispatcher();

  function close() {
    dispatch("close");
  }

  function toggleSubtask(st) {
    // local only; caller can implement persistence
    st.completed = !st.completed;
  }
</script>

<Modal {open} size="xl" position="right" on:close={close}>
  {#if task}
    <div
      class="h-full w-full flex flex-col bg-background-light dark:bg-background-dark"
    >
      <!-- Header -->
      <div
        class="h-16 px-6 border-b flex items-center justify-between bg-surface-light/50 dark:bg-background-dark/95"
      >
        <div
          class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400"
        >
          <span class="material-symbols-outlined">view_kanban</span>
          <div class="flex flex-col">
            <div class="text-sm font-semibold">
              {task.code ?? "#" + (task.id ?? "")}
            </div>
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
          <div class="space-y-6">
            <div class="group relative">
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {task.title}
              </h1>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase"
                  >Estado</label
                >
                <div class="mt-2">
                  <span
                    class="px-3 py-1 rounded text-sm bg-primary/10 text-primary"
                    >{task.status || "Por Hacer"}</span
                  >
                </div>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase"
                  >Responsables</label
                >
                <div class="mt-2 flex items-center gap-2">
                  {#if task.assignees && task.assignees.length}
                    {#each task.assignees as a}
                      <div
                        class="w-9 h-9 rounded-full border-[3px] border-white bg-gray-200 dark:bg-surface-dark bg-cover bg-center"
                        style="background-image: url('{a.avatar}')"
                      ></div>
                    {/each}
                  {:else}
                    <div class="text-sm text-gray-500 italic">
                      Sin responsables
                    </div>
                  {/if}
                </div>
              </div>
            </div>

            <div>
              <h2
                class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
              >
                <span class="material-symbols-outlined text-gray-400"
                  >description</span
                >Descripción
              </h2>
              <div
                class="mt-3 bg-surface-light dark:bg-surface-dark border rounded-xl p-4"
              >
                <div
                  class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300"
                >
                  {@html task.description ?? "<em>No hay descripción</em>"}
                </div>
              </div>
            </div>

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
                  >{task.subtasks
                    ? task.subtasks.filter((s) => s.completed).length +
                      "/" +
                      task.subtasks.length
                    : "0/0"} Completado</span
                >
              </div>

              <div class="mt-3 space-y-2">
                {#if task.subtasks && task.subtasks.length}
                  {#each task.subtasks as st}
                    <div
                      class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-surface-dark/50 transition-colors cursor-pointer"
                    >
                      <div class="flex items-center mt-0.5">
                        <input
                          type="checkbox"
                          bind:checked={st.completed}
                          on:change={() => toggleSubtask(st)}
                          class="w-5 h-5"
                        />
                      </div>
                      <div class="flex-1 text-sm">{st.title}</div>
                    </div>
                  {/each}
                {:else}
                  <div class="text-sm text-gray-500 italic">Sin subtareas</div>
                {/if}
              </div>
            </div>

            <div>
              <h2
                class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
              >
                <span class="material-symbols-outlined text-gray-400">link</span
                >Dependencias
              </h2>
              <div class="mt-3 space-y-2">
                {#if task.dependencies && task.dependencies.length}
                  {#each task.dependencies as d}
                    <div
                      class="bg-white dark:bg-background-dark border rounded-md p-3 flex items-center justify-between shadow-sm"
                    >
                      <div class="flex items-center gap-3">
                        <div
                          class="h-8 w-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-xs"
                        >
                          {d.id}
                        </div>
                        <div>
                          <p class="text-sm font-medium">{d.title}</p>
                          <div class="text-xs text-gray-500">{d.status}</div>
                        </div>
                      </div>
                      <span class="material-symbols-outlined text-gray-400"
                        >open_in_new</span
                      >
                    </div>
                  {/each}
                {:else}
                  <div class="text-sm text-gray-500 italic">
                    Sin dependencias
                  </div>
                {/if}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 border-t bg-background-light dark:bg-background-dark">
        <div class="flex gap-2">
          <input
            class="flex-1 pl-3 pr-4 py-2 bg-white dark:bg-surface-dark border rounded-lg text-sm"
            placeholder="Escribe un comentario..."
          />
          <button class="px-4 py-2 bg-primary text-white rounded-lg"
            >Enviar</button
          >
        </div>
      </div>
    </div>
  {/if}
</Modal>

<style>
  .prose p {
    margin: 0;
  }
</style>
