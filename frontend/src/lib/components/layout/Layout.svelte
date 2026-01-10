<script>
	import Sidebar from './Sidebar.svelte';
	import Header from './Header.svelte';

	/**
	 * Main Layout component that combines sidebar and header
	 * @param {string} title - Page title for header
	 * @param {Array} breadcrumbs - Navigation breadcrumbs
	 * @param {boolean} showSearch - Show/hide search in header
	 * @param {Array} headerActions - Header action buttons
	 * @param {string} activeNavItem - Active navigation item in sidebar
	 * @param {Object|null} user - User information
	 * @param {Array} boards - Favorite boards for sidebar
	 * @param {Function} onSearch - Search callback
	 * @param {Function} onHeaderAction - Header action callback
	 * @param {Function} onNavigation - Navigation callback
	 * @param {Function} onCreateBoard - Create board callback
	 */
	export let title = '';
	export let breadcrumbs = [];
	export let showSearch = true;
	export let headerActions = [];
	export let activeNavItem = 'boards';
	export let user = null;
	export let boards = [];
	export let onSearch = () => {};
	export let onHeaderAction = () => {};
	export let onNavigation = () => {};
	export let onCreateBoard = () => {};

	// Mobile sidebar state
	let mobileMenuOpen = false;

	function handleMobileMenuToggle() {
		mobileMenuOpen = !mobileMenuOpen;
	}

	function handleNavigation(item) {
		// Close mobile menu when navigating
		mobileMenuOpen = false;
		onNavigation(item);
	}

	function handleSearch(searchValue) {
		onSearch(searchValue);
	}

	function handleHeaderAction(action) {
		onHeaderAction(action);
	}

	function handleCreateBoard() {
		onCreateBoard();
	}

	// Close mobile menu when clicking outside
	function handleBackdropClick() {
		mobileMenuOpen = false;
	}
</script>

<div class="app-layout">
	<!-- Desktop Sidebar -->
	<Sidebar 
		{activeNavItem}
		{user}
		{boards}
		collapsed={false}
		onNavigate={handleNavigation}
		onCreateBoard={handleCreateBoard}
	/>

	<!-- Mobile Sidebar Backdrop -->
	{#if mobileMenuOpen}
		<div 
			class="fixed inset-0 bg-black/50 z-40 lg:hidden"
			on:click={handleBackdropClick}
			on:keydown={(e) => e.key === 'Escape' && handleBackdropClick()}
			role="button"
			tabindex="0"
		></div>
	{/if}

	<!-- Mobile Sidebar -->
	<div class="lg:hidden">
		<div 
			class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out {mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'}"
		>
			<Sidebar 
				{activeNavItem}
				{user}
				{boards}
				collapsed={false}
				onNavigate={handleNavigation}
				onCreateBoard={handleCreateBoard}
			/>
		</div>
	</div>

	<!-- Main Content Area -->
	<main class="main-content">
		<!-- Header -->
		<Header 
			{title}
			{breadcrumbs}
			{showSearch}
			actions={headerActions}
			{onSearch}
			onActionClick={handleHeaderAction}
			onMobileMenuToggle={handleMobileMenuToggle}
		/>

		<!-- Page Content -->
		<div class="flex-1 overflow-y-auto custom-scrollbar">
			<slot />
		</div>
	</main>
</div>

<style>
	/* Ensure proper scrolling behavior */
	.custom-scrollbar {
		scrollbar-width: thin;
		scrollbar-color: rgba(156, 163, 175, 0.3) transparent;
	}

	.custom-scrollbar::-webkit-scrollbar {
		width: 6px;
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