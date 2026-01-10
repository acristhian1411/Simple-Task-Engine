<script>
  import { onMount } from "svelte";
  import { getBoardsWithLists } from "$lib/api/boards.js";
  import { auth, refreshMe } from "$lib/stores/auth.js";
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
      error =
        e?.response?.data?.message ?? e?.message ?? "Error loading boards";
    } finally {
      loading = false;
    }
  }

  function handleCreateBoard() {
    // TODO: Implement create board modal or navigation
    console.log("Create board functionality to be implemented");
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

<!-- HEADER SECTION -->
<header
  class="w-full px-8 pt-6 pb-2 flex flex-col gap-4 z-10 bg-background-light dark:bg-background-dark"
>
  <!-- Breadcrumbs -->

  <!-- Title & Actions Row -->
</header>

<!-- CONTENT GRID -->
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
