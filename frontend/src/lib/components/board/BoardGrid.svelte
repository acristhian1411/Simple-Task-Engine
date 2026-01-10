<script>
	import { goto } from '$app/navigation';
	import BoardCard from './BoardCard.svelte';

	export let boards = [];
	export let loading = false;
	export let error = '';
	export let searchQuery = '';
	export let onCreateBoard = null;

	// Filter boards based on search query
	$: filteredBoards = boards.filter(board => {
		if (!searchQuery) return true;
		const query = searchQuery.toLowerCase();
		return (
			board.title?.toLowerCase().includes(query) ||
			board.description?.toLowerCase().includes(query)
		);
	});

	function handleBoardClick(board) {
		goto(`/board/${board.id}`);
	}

	function handleCreateBoard() {
		if (onCreateBoard) {
			onCreateBoard();
		} else {
			// Default behavior - could open a modal or navigate to create page
			console.log('Create new board clicked');
		}
	}
</script>

<div class="flex-1 overflow-y-auto custom-scrollbar px-8 py-6">
	{#if loading}
		<!-- Loading State -->
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-10">
			{#each Array(8) as _, i}
				<div class="board-card animate-pulse">
					<div class="h-24 w-full bg-slate-200 dark:bg-slate-700"></div>
					<div class="p-4 flex flex-col flex-1">
						<div class="flex justify-between items-start mb-2">
							<div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-16"></div>
						</div>
						<div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mb-1"></div>
						<div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-full mb-4"></div>
						<div class="mt-auto flex flex-col gap-3">
							<div class="flex flex-col gap-1">
								<div class="flex justify-between">
									<div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-12"></div>
									<div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-8"></div>
								</div>
								<div class="progress-bar">
									<div class="h-full bg-slate-200 dark:bg-slate-700 rounded-full"></div>
								</div>
							</div>
							<div class="flex items-center justify-between border-t border-border-light dark:border-border-dark pt-3 mt-1">
								<div class="avatar-group">
									<div class="avatar avatar-sm bg-slate-200 dark:bg-slate-700 rounded-full"></div>
									<div class="avatar avatar-sm bg-slate-200 dark:bg-slate-700 rounded-full"></div>
								</div>
								<div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-12"></div>
							</div>
						</div>
					</div>
				</div>
			{/each}
		</div>
	{:else if error}
		<!-- Error State -->
		<div class="flex items-center justify-center min-h-[400px]">
			<div class="text-center">
				<span class="material-symbols-outlined text-6xl text-red-500 mb-4 block">error</span>
				<h3 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark mb-2">
					Error al cargar tableros
				</h3>
				<p class="text-text-sec-light dark:text-text-sec-dark mb-4">
					{error}
				</p>
				<button 
					class="btn-primary"
					on:click={() => window.location.reload()}
				>
					<span class="material-symbols-outlined text-[18px]">refresh</span>
					Reintentar
				</button>
			</div>
		</div>
	{:else}
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-10">
			<!-- Create New Board Card -->
			<button 
				class="create-board-card"
				on:click={handleCreateBoard}
			>
				<div class="h-12 w-12 rounded-full bg-primary/10 dark:bg-surface-dark flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
					<span class="material-symbols-outlined text-primary text-[28px]">add</span>
				</div>
				<h3 class="text-text-main-light dark:text-text-main-dark font-semibold text-lg">
					Crear Nuevo Tablero
				</h3>
				<p class="text-text-sec-light dark:text-text-sec-dark text-sm mt-1">
					Empieza un nuevo proyecto
				</p>
			</button>

			<!-- Board Cards -->
			{#each filteredBoards as board (board.id)}
				<BoardCard {board} onClick={handleBoardClick} />
			{/each}

			<!-- Empty State when no boards match search -->
			{#if filteredBoards.length === 0 && searchQuery}
				<div class="col-span-full flex items-center justify-center min-h-[200px]">
					<div class="text-center">
						<span class="material-symbols-outlined text-4xl text-text-sec-light dark:text-text-sec-dark mb-2 block">search_off</span>
						<h3 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark mb-1">
							No se encontraron tableros
						</h3>
						<p class="text-text-sec-light dark:text-text-sec-dark">
							Intenta con otros términos de búsqueda
						</p>
					</div>
				</div>
			{/if}

			<!-- Empty State when no boards exist -->
			{#if boards.length === 0 && !searchQuery}
				<div class="col-span-full flex items-center justify-center min-h-[400px]">
					<div class="text-center">
						<span class="material-symbols-outlined text-6xl text-text-sec-light dark:text-text-sec-dark mb-4 block">view_kanban</span>
						<h3 class="text-xl font-semibold text-text-main-light dark:text-text-main-dark mb-2">
							¡Bienvenido a KanbanFlow!
						</h3>
						<p class="text-text-sec-light dark:text-text-sec-dark mb-6 max-w-md">
							Aún no tienes tableros. Crea tu primer tablero para empezar a organizar tus proyectos.
						</p>
						<button 
							class="btn-primary"
							on:click={handleCreateBoard}
						>
							<span class="material-symbols-outlined text-[18px]">add</span>
							Crear mi primer tablero
						</button>
					</div>
				</div>
			{/if}
		</div>
	{/if}
</div>

<style>
	@keyframes pulse {
		0%, 100% {
			opacity: 1;
		}
		50% {
			opacity: 0.5;
		}
	}
	
	.animate-pulse {
		animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
	}
</style>