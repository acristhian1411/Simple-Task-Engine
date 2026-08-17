<script>
  import { onMount } from "svelte";
  import { getBoardsWithLists } from "$lib/api/boards.js";
  import { refreshMe } from "$lib/stores/auth.js";
  import { createBoardOpen } from "$lib/stores/ui.js";
  import { goto } from "$app/navigation";
  import BoardGrid from "$lib/components/board/BoardGrid.svelte";

  let boards = [];
  let loading = true;
  let error = "";
  let searchQuery = "";

  async function loadBoards() {
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
      error = e?.response?.data?.message ?? e?.message ?? "Error cargando tableros";
    } finally {
      loading = false;
    }
  }

  function handleCreateBoard() {
    createBoardOpen.set(true);
  }

  onMount(loadBoards);
</script>

<svelte:head>
  <title>Panel de Control - KanbanFlow</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet"
  />
</svelte:head>

<BoardGrid
  {boards}
  {loading}
  {error}
  {searchQuery}
  onCreateBoard={handleCreateBoard}
/>

<style>
  .material-symbols-outlined {
    font-variation-settings:
      "FILL" 0,
      "wght" 400,
      "GRAD" 0,
      "opsz" 24;
  }
</style>