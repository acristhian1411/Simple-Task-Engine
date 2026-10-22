<script>
	import Sidebar from './Sidebar.svelte';
	import { sidebarOpen } from '$lib/stores/ui.js';

	/**
	 * Main Layout component that combines sidebar and content
	 * @param {string} activeNavItem - Active navigation item in sidebar
	 * @param {Object|null} user - User information
	 * @param {Function} onNavigation - Navigation callback
	 */
	export let activeNavItem = 'boards';
	export let user = null;
	export let onNavigation = () => {};

	function handleNavigation(item) {
		// Close mobile menu when navigating
		sidebarOpen.set(false);
		onNavigation(item);
	}

	function toggleMobileMenu() {
		sidebarOpen.update((v) => !v);
	}

	// Close mobile menu when clicking outside
	function handleBackdropClick() {
		sidebarOpen.set(false);
	}
</script>

<div class="app-layout">
	<!-- Desktop Sidebar (oculto en pantallas chicas, ahí se usa el menú móvil) -->
	<Sidebar
		activeItem={activeNavItem}
		{user}
		collapsed={true}
		onNavigate={handleNavigation}
	/>

	<!-- Mobile Sidebar Backdrop -->
	{#if $sidebarOpen}
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
			class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out {$sidebarOpen ? 'translate-x-0' : '-translate-x-full'}"
		>
			<Sidebar
				activeItem={activeNavItem}
				{user}
				collapsed={false}
				onNavigate={handleNavigation}
			/>
		</div>
	</div>

	<!-- Main Content Area -->
	<main class="main-content">
		<!-- Barra superior en pantallas chicas: ocupa su propio espacio para no tapar el contenido de la página -->
		<div class="lg:hidden shrink-0 flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
			<button
				class="p-2 text-gray-600 dark:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
				on:click={toggleMobileMenu}
				type="button"
				aria-label="Abrir menú"
			>
				<span class="material-symbols-outlined">menu</span>
			</button>
			<span class="text-sm font-bold text-gray-900 dark:text-white">Maius Kanban</span>
		</div>

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