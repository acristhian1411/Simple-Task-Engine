<script>
	/**
	 * Sidebar component for navigation and user profile
	 * @param {string} activeItem - Current active navigation item
	 * @param {Object|null} user - User information object
	 * @param {Array} boards - Array of favorite boards for quick access
	 * @param {boolean} collapsed - Whether sidebar is collapsed (mobile)
	 * @param {Function} onCreateBoard - Callback for create board action
	 * @param {Function} onNavigate - Callback for navigation
	 */
	export let activeItem = 'boards';
	export let user = null;
	export const boards = [];
	export let collapsed = false;
	export let onCreateBoard = () => {};
	export let onNavigate = () => {};

	// Navigation items configuration
	const navItems = [
		{ id: 'dashboard', label: 'Panel', icon: 'dashboard', href: '/' },
		{ id: 'boards', label: 'Mis Tableros', icon: 'view_kanban', href: '/boards' },
		{ id: 'members', label: 'Miembros', icon: 'group', href: '/members' },
		{ id: 'reports', label: 'Reportes', icon: 'analytics', href: '/reports' },
		{ id: 'settings', label: 'Configuración', icon: 'settings', href: '/settings' }
	];

	// Default user if none provided
	const defaultUser = {
		name: 'Alex Morgan',
		email: 'alex@kanbanflow.com',
		avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBfj0AMzX5L2kvHqL3L-WDYAhtVnM9_7yZg_mm2HEPv6tezHc2M19Hjpe-v6zKeDBuWbfqtyzc8GHEbjBedXp5OzSH-nz3_dx4bRMaYFA3Ni74B8EXDc7y6TeHf6N385OIuaULnuQRj64iDbDNBbDGUW1YU9i0w3DWMegVeDJ5zyOOWyLQejY8lOxnRW5haUEZrmynKNHSwjU54zVmSZowIDPQMjunaJAhGhR24CCqb7nDiT8RmScTOpX-6ftp_qoYspDmS85eo6-I'
	};

	$: currentUser = user || defaultUser;

	function handleNavClick(item) {
		onNavigate(item);
	}

	function handleCreateBoard() {
		onCreateBoard();
	}
</script>

<!-- Material Symbols CSS -->
<svelte:head>
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
</svelte:head>

<aside class="sidebar {collapsed ? 'hidden lg:flex' : 'flex'}">
	<div class="p-6 pb-2">
		<!-- Logo and Brand -->
		<div class="flex gap-3 items-center mb-8">
			<div 
				class="bg-center bg-no-repeat bg-cover rounded-lg size-10 shadow-sm" 
				style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCzog1g7AwjhlmKjmczfNhbk32yPqG44hf83HghRDsQ41oh5bRgtvWeYHaSO-sazP1sDRA9MKUY2kn2gs4Hnr2TU9UIUmsCtcQIJ55Ys5Snf82H1TDxS7wcZS7hSEChJHGOju-fbLio4_PbnaVGqiNTIe-gSCwmtoBhdMvH43d62Mj7SLfUWg2LjUPOfYysTOzBY-mBlsrX1yiwGC1PvtwlPtHWYLR6GVIoBGoiLzOHeZCFF7nZ3Ua5joqMTSwGDugCSuSQjpK-FVE')"
				alt="KanbanFlow Logo"
			></div>
			<div class="flex flex-col">
				<h1 class="text-gray-900 dark:text-white text-base font-bold leading-none">KanbanFlow</h1>
				<p class="text-gray-600 dark:text-gray-400 text-xs font-medium mt-1">Gestión Pro</p>
			</div>
		</div>

		<!-- Navigation Items -->
		<div class="flex flex-col gap-1">
			{#each navItems as item}
				<button
					class={activeItem === item.id ? 'nav-item-active' : 'nav-item'}
					on:click={() => handleNavClick(item)}
					type="button"
				>
					<span class="material-symbols-outlined text-[24px] {activeItem === item.id ? '' : 'group-hover:text-indigo-600 transition-colors'}">{item.icon}</span>
					<p class="text-sm font-{activeItem === item.id ? 'bold' : 'medium'} leading-normal">{item.label}</p>
				</button>
			{/each}
		</div>
	</div>

	<!-- Bottom Section -->
	<div class="mt-auto p-6 flex flex-col gap-4">
		<!-- Create Board Button -->
		<button 
			class="btn-primary"
			on:click={handleCreateBoard}
			type="button"
		>
			<span class="material-symbols-outlined mr-2 text-[20px]">add</span>
			<span class="truncate">Crear Tablero</span>
		</button>

		<!-- User Profile -->
		<div class="border-t border-gray-200 dark:border-gray-700 pt-4">
			<button 
				class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors w-full text-left"
				type="button"
			>
				<div 
					class="w-8 h-8 rounded-full bg-cover bg-center" 
					style="background-image: url('{currentUser.avatar}')"
					alt="{currentUser.name} profile picture"
				></div>
				<div class="flex flex-col min-w-0 flex-1">
					<p class="text-gray-900 dark:text-white text-sm font-medium leading-none truncate">{currentUser.name}</p>
					<p class="text-gray-600 dark:text-gray-400 text-xs mt-1 truncate">{currentUser.email}</p>
				</div>
			</button>
		</div>
	</div>
</aside>

<style>
	.material-symbols-outlined {
		font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
	}
</style>