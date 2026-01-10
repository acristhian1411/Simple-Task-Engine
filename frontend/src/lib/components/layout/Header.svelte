<script>
	/**
	 * Header component with breadcrumbs, search, and actions
	 * @param {string} title - Page title
	 * @param {Array} breadcrumbs - Navigation breadcrumbs array
	 * @param {boolean} showSearch - Show/hide search bar
	 * @param {Array} actions - Header action buttons
	 * @param {Function} onSearch - Search callback
	 * @param {Function} onActionClick - Action button callback
	 * @param {Function} onMobileMenuToggle - Mobile menu toggle callback
	 */
	export let title = '';
	export let breadcrumbs = [];
	export let showSearch = true;
	export let actions = [];
	export let onSearch = () => {};
	export let onActionClick = () => {};
	export let onMobileMenuToggle = () => {};

	let searchValue = '';

	// Default actions if none provided
	const defaultActions = [
		{
			id: 'grid',
			icon: 'grid_view',
			label: 'Grid View',
			active: true,
			type: 'toggle'
		},
		{
			id: 'list',
			icon: 'view_list', 
			label: 'List View',
			active: false,
			type: 'toggle'
		},
		{
			id: 'filter',
			icon: 'filter_list',
			label: 'Filtrar',
			type: 'button'
		}
	];

	$: currentActions = actions.length > 0 ? actions : defaultActions;

	function handleSearch(event) {
		searchValue = event.target.value;
		onSearch(searchValue);
	}

	function handleActionClick(action) {
		onActionClick(action);
	}

	function handleMobileMenuToggle() {
		onMobileMenuToggle();
	}
</script>

<!-- Material Symbols CSS -->
<svelte:head>
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
</svelte:head>

<header class="w-full px-8 pt-6 pb-2 flex flex-col gap-4 z-10 bg-gray-50 dark:bg-gray-900">
	<!-- Breadcrumbs -->
	{#if breadcrumbs.length > 0}
		<div class="flex gap-2 items-center text-sm">
			{#each breadcrumbs as crumb, index}
				{#if index > 0}
					<span class="text-gray-500 dark:text-gray-500 font-medium">/</span>
				{/if}
				{#if crumb.href && index < breadcrumbs.length - 1}
					<a 
						href={crumb.href}
						class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 font-medium transition-colors"
					>
						{crumb.label}
					</a>
				{:else}
					<span class="text-gray-900 dark:text-white font-medium">{crumb.label}</span>
				{/if}
			{/each}
		</div>
	{/if}

	<!-- Title & Actions Row -->
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
		<!-- Mobile Menu Button & Title -->
		<div class="flex items-center gap-4">
			<!-- Mobile Menu Toggle (visible only on small screens) -->
			<button 
				class="lg:hidden text-gray-600 dark:text-white p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
				on:click={handleMobileMenuToggle}
				type="button"
				aria-label="Toggle mobile menu"
			>
				<span class="material-symbols-outlined">menu</span>
			</button>

			<h1 class="text-gray-900 dark:text-white text-3xl font-bold tracking-tight">{title}</h1>
		</div>

		<div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
			<!-- Search Bar -->
			{#if showSearch}
				<div class="relative group min-w-[280px]">
					<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
						<span class="material-symbols-outlined text-gray-500 dark:text-gray-400 group-focus-within:text-indigo-600 transition-colors">search</span>
					</div>
					<input 
						class="search-input"
						placeholder="Buscar tableros..."
						type="text"
						bind:value={searchValue}
						on:input={handleSearch}
					/>
				</div>
			{/if}

			<!-- View Toggles / Filters -->
			<div class="flex bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm h-[42px] items-center border border-gray-200 dark:border-gray-700">
				{#each currentActions as action, index}
					{#if action.type === 'toggle'}
						<button 
							class={action.active 
								? 'p-1.5 rounded bg-gray-100 dark:bg-gray-700 text-indigo-600 shadow-sm' 
								: 'p-1.5 rounded text-gray-600 dark:text-gray-400 hover:text-indigo-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors'
							}
							on:click={() => handleActionClick(action)}
							aria-label={action.label}
							type="button"
						>
							<span class="material-symbols-outlined text-[20px]">{action.icon}</span>
						</button>
					{:else if action.type === 'button'}
						{#if index > 0 && currentActions[index - 1].type === 'toggle'}
							<div class="w-px h-4 bg-gray-200 dark:border-gray-700 mx-2"></div>
						{/if}
						<button 
							class="flex items-center gap-1.5 px-2 py-1 rounded text-gray-600 dark:text-gray-400 hover:text-indigo-600 transition-colors text-sm font-medium"
							on:click={() => handleActionClick(action)}
							type="button"
						>
							<span class="material-symbols-outlined text-[18px]">{action.icon}</span>
							<span>{action.label}</span>
						</button>
					{/if}
				{/each}
			</div>
		</div>
	</div>
</header>

<style>
	.material-symbols-outlined {
		font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
	}
</style>