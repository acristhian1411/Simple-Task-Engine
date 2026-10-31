<script>
  import { createEventDispatcher } from "svelte";
  import Modal from "$lib/components/ui/Modal.svelte";
  import {
    getBoardMembers,
    addBoardMember,
    removeBoardMember,
  } from "$lib/api/boards.js";

  export let show = false;
  export let board = null;

  const dispatch = createEventDispatcher();

  let members = [];
  let email = "";
  let error = "";
  let loading = false;
  let busy = false;

  // Carga los miembros cada vez que se abre el modal
  $: if (show) {
    loadMembers();
  }

  async function loadMembers() {
    if (!board) return;
    loading = true;
    error = "";
    try {
      members = await getBoardMembers(board.id);
    } catch (e) {
      error =
        e?.response?.data?.error ?? e?.message ?? "No se pudieron cargar los miembros";
    } finally {
      loading = false;
    }
  }

  async function addMember() {
    if (!email.trim() || busy) return;
    busy = true;
    error = "";
    try {
      members = await addBoardMember(board.id, email.trim());
      email = "";
    } catch (e) {
      error =
        e?.response?.data?.errors?.email?.[0] ??
        e?.response?.data?.error ??
        e?.message ??
        "No se pudo agregar el usuario";
    } finally {
      busy = false;
    }
  }

  async function removeMember(member) {
    busy = true;
    error = "";
    try {
      members = await removeBoardMember(board.id, member.id);
    } catch (e) {
      error =
        e?.response?.data?.error ?? e?.message ?? "No se pudo quitar el usuario";
    } finally {
      busy = false;
    }
  }

  function close() {
    email = "";
    error = "";
    dispatch("close");
  }
</script>

<Modal open={show} size="md" position="center" on:close={close}>
  <div class="p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">
        Compartir tablero
      </h2>
      <button
        class="p-1 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        on:click={close}
        aria-label="Cerrar"
        type="button"
      >
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <form
      class="flex items-end gap-2"
      on:submit|preventDefault={addMember}
    >
      <div class="flex-1">
        <label
          class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          for="member-email"
        >
          Email del usuario
        </label>
        <input
          id="member-email"
          class="form-input mt-1"
          type="email"
          bind:value={email}
          placeholder="usuario@ejemplo.com"
          required
        />
      </div>
      <button class="btn-primary" type="submit" disabled={busy || loading}>
        {busy ? "Agregando..." : "Agregar"}
      </button>
    </form>

    {#if error}
      <div
        class="mt-3 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-600 dark:text-red-300"
      >
        {error}
      </div>
    {/if}

    <div class="mt-5">
      <h3
        class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"
      >
        Con acceso
      </h3>

      {#if loading}
        <p class="text-sm text-gray-500 dark:text-gray-400">Cargando...</p>
      {:else}
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          {#each members as member (member.id)}
            <li class="flex items-center justify-between py-2">
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {member.name}
                  {#if member.role === "owner"}
                    <span
                      class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                    >
                      DUEÑO
                    </span>
                  {/if}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                  {member.email}
                </p>
              </div>
              {#if member.role !== "owner"}
                <button
                  class="text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50"
                  type="button"
                  disabled={busy}
                  on:click={() => removeMember(member)}
                  aria-label={`Quitar a ${member.name}`}
                >
                  <span class="material-symbols-outlined text-[20px]">person_remove</span>
                </button>
              {/if}
            </li>
          {/each}
        </ul>
      {/if}
    </div>
  </div>
</Modal>
