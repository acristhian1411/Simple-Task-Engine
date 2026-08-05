<script>
  import favicon from "$lib/assets/favicon.svg";
  import { Layout } from "$lib/components/layout";
  import "../app.css";
  import { auth, refreshMe } from "$lib/stores/auth";
  import { onMount } from "svelte";
  import { page } from "$app/stores";

  // Layout configuration
  let title = "Mis Tableros";
  let breadcrumbs = [{ label: "Inicio", href: "/" }, { label: "Tableros" }];
  let activeNavItem = "boards";

  // Event handlers
  function handleSearch(searchValue) {
    console.log("Search:", searchValue);
  }

  function handleHeaderAction(action) {
    console.log("Header action:", action);
  }

  function handleNavigation(item) {
    activeNavItem = item.id;
  }

  function handleCreateBoard() {
    console.log("Create board clicked");
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
    onNavigation={handleNavigation}
    onCreateBoard={handleCreateBoard}
  >
    <slot />
  </Layout>
{:else}
  <!-- If not authenticated, render pages directly (login/public pages) -->
  <slot />
{/if}
