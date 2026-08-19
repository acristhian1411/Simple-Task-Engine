<script>
  import favicon from "$lib/assets/favicon.svg";
  import { Layout } from "$lib/components/layout";
  import "../app.css";
  import { auth, refreshMe } from "$lib/stores/auth";
  import { createBoardOpen } from "$lib/stores/ui";
  import CreateBoardModal from "$lib/components/board/CreateBoardModal.svelte";
  import { onMount } from "svelte";
  import { page } from "$app/stores";

  // Navigation activation based on current path
  $: pathname = $page.url.pathname;
  $: activeNavItem = pathname.startsWith("/components/explorer")
    ? "explorer"
    : pathname.startsWith("/components/impact")
      ? "impact"
      : pathname.startsWith("/board")
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
  <Layout {activeNavItem} user={$auth.user}>
    <slot />
  </Layout>
  <CreateBoardModal show={$createBoardOpen} on:close={closeCreateBoard} />
{:else}
  <!-- If not authenticated, render pages directly (login/public pages) -->
  <slot />
{/if}