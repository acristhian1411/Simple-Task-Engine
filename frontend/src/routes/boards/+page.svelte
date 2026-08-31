<script>
  import { onMount } from "svelte";
  import { getBoardsWithLists } from "$lib/api/boards.js";
  import { refreshMe } from "$lib/stores/auth.js";
  import { createBoardOpen } from "$lib/stores/ui.js";
  import { goto } from "$app/navigation";
  import BoardGrid from "$lib/components/board/BoardGrid.svelte";
  import Header from "$lib/components/layout/Header.svelte";
  import { Plus } from "lucide-svelte";

  let boards = [];
  let loading = true;
  let error = "";
  let searchQuery = "";

  const breadcrumbs = [{ label: "Inicio", href: "/" }, { label: "Tableros" }];

  async function loadBoards() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) {
        goto("/login");
        return;
      }
      const res = await getBoardsWithLists({ per_page: 100 });
      boards = res;
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
  <title>Tableros - KanbanFlow</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet"
  />
</svelte:head>

<div>
  <Header
    title="Mis Tableros"
    {breadcrumbs}
    showSearch={true}
    onSearch={(value) => (searchQuery = value)}
  />

  <div class="flex justify-end px-8 py-4">
    <button
      type="button"
      onclick={handleCreateBoard}
      class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
    >
      <Plus size={18} />
      <span>Crear Tablero</span>
    </button>
  </div>

  <BoardGrid
    {boards}
    {loading}
    {error}
    {searchQuery}
    onCreateBoard={handleCreateBoard}
  />
</div>
