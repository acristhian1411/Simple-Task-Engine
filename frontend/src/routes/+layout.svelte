<script>
	import favicon from '$lib/assets/favicon.svg';
	import { Layout } from '$lib/components/layout';
	import '../app.css';

	let { children } = $props();

	// Layout configuration
	let title = 'Mis Tableros';
	let breadcrumbs = [
		{ label: 'Inicio', href: '/' },
		{ label: 'Tableros' }
	];
	let activeNavItem = $state('boards');
	let user = null; // Will use default user from Sidebar component

	// Event handlers
	function handleSearch(searchValue) {
		console.log('Search:', searchValue);
		// TODO: Implement search functionality
	}

	function handleHeaderAction(action) {
		console.log('Header action:', action);
		// TODO: Implement header actions (view toggles, filters)
	}

	function handleNavigation(item) {
		console.log('Navigation:', item);
		// TODO: Implement navigation routing
		activeNavItem = item.id;
	}

	function handleCreateBoard() {
		console.log('Create board clicked');
		// TODO: Implement create board functionality
	}
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
</svelte:head>

<Layout
	{title}
	{breadcrumbs}
	showSearch={true}
	headerActions={[]}
	{activeNavItem}
	{user}
	boards={[]}
	onSearch={handleSearch}
	onHeaderAction={handleHeaderAction}
	onNavigation={handleNavigation}
	onCreateBoard={handleCreateBoard}
>
	{@render children()}
</Layout>
