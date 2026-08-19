<script>
  import favicon from "$lib/assets/favicon.svg";
  import { Layout } from "$lib/components/layout";
  import "../app.css";
  import { auth, refreshMe } from "$lib/stores/auth";
  import { createBoardOpen } from "$lib/stores/ui";
  import CreateBoardModal from "$lib/components/board/CreateBoardModal.svelte";
  import { onMount } from "svelte";
  import { page } from "$app/stores";

  // Layout configuration
  let title = "Mis Tableros";
  let breadcrumbs = [{ label: "Inicio", href: "/" }, { label: "Tableros" }];

  // Navigation activation based on current path
  $: pathname = $page.url.pathname;
  $: activeNavItem = pathname.startsWith("/board")
    ? "boards"
    : pathname.startsWith("/bugs")
      ? "bugs"
      : pathname.startsWith("/components")
        ? "components"
        : pathname.startsWith("/tests")
          ? "tests"
          : pathname === "/"
            ? "dashboard"
            : "boards";

  // Event handlers
  function handleSearch(searchValue) {
    console.log("Search:", searchValue);
  }

  function handleHeaderAction(action) {
    console.log("Header action:", action);
  }

  function handleCreateBoard() {
    createBoardOpen.set(true);
  }

  function closeCreateBoard() {
    createBoardOpen.set(false);
  }

  onMount(async () => {
    await refreshMe();
  });
</script>

<svelte:head>
  <link rel="icon" href={favicon} />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
</svelte:head>

{#if $auth?.user}
  <Layout
    {title}
    {breadcrumbs}
    showSearch={true}
    headerActions={[]}
    {activeNavItem}
    user={$auth.user}
    boards={[]}
    onSearch={handleSearch}
    onHeaderAction={handleHeaderAction}
    onCreateBoard={handleCreateBoard}
  >
    <slot />
  </Layout>
  <CreateBoardModal show={$createBoardOpen} on:close={closeCreateBoard} />
{:else}
  <!-- If not authenticated, render pages directly (login/public pages) -->
  <slot />
{/if}