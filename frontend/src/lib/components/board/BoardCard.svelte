<script>
	export let board = {};
	export let onClick = null;

	// Calculate progress based on lists and tasks if available
	function calculateProgress(board) {
		if (!board.lists || board.lists.length === 0) return 0;
		
		let totalTasks = 0;
		let completedTasks = 0;
		
		board.lists.forEach(list => {
			if (list.tasks) {
				totalTasks += list.tasks.length;
				completedTasks += list.tasks.filter(task => task.status === 'done').length;
			}
		});
		
		return totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
	}

	// Get team members from board data (placeholder for now)
	function getTeamMembers(board) {
		// This would come from actual board member data in a real implementation
		return [
			{ id: 1, name: 'Member 1', avatar: 'https://via.placeholder.com/32' },
			{ id: 2, name: 'Member 2', avatar: 'https://via.placeholder.com/32' }
		];
	}

	// Get category color based on board title or category
	function getCategoryColor(board) {
		const colors = [
			{ bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-800 dark:text-blue-300', progress: 'bg-primary' },
			{ bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-800 dark:text-orange-300', progress: 'bg-orange-500' },
			{ bg: 'bg-purple-100 dark:bg-purple-900/30', text: 'text-purple-800 dark:text-purple-300', progress: 'bg-purple-500' },
			{ bg: 'bg-emerald-100 dark:bg-emerald-900/30', text: 'text-emerald-800 dark:text-emerald-300', progress: 'bg-emerald-500' }
		];
		
		// Simple hash to consistently assign colors based on board id
		const index = board.id ? board.id % colors.length : 0;
		return colors[index];
	}

	// Get category name (placeholder)
	function getCategoryName(board) {
		const categories = ['Desarrollo', 'Marketing', 'RRHH', 'Finanzas'];
		const index = board.id ? board.id % categories.length : 0;
		return categories[index];
	}

	// Format relative time
	function getRelativeTime(board) {
		if (!board.updated_at) return 'Hoy';
		
		const now = new Date();
		const updated = new Date(board.updated_at);
		const diffTime = Math.abs(now - updated);
		const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
		
		if (diffDays === 1) return 'Hoy';
		if (diffDays <= 7) return `${diffDays} días`;
		if (diffDays <= 30) return `${Math.ceil(diffDays / 7)} sem`;
		return `${Math.ceil(diffDays / 30)} mes`;
	}

	$: progress = calculateProgress(board);
	$: teamMembers = getTeamMembers(board);
	$: categoryColor = getCategoryColor(board);
	$: categoryName = getCategoryName(board);
	$: relativeTime = getRelativeTime(board);

	function handleClick() {
		if (onClick) {
			onClick(board);
		}
	}
</script>

<div class="board-card" on:click={handleClick} on:keydown={(e) => e.key === 'Enter' && handleClick()} role="button" tabindex="0">
	<!-- Cover Image -->
	<div class="h-24 w-full bg-gradient-to-r from-primary to-primary-dark relative">
		<div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
	</div>
	
	<!-- Card Content -->
	<div class="p-4 flex flex-col flex-1">
		<!-- Category and Menu -->
		<div class="flex justify-between items-start mb-2">
			<span class="status-badge {categoryColor.bg} {categoryColor.text}">
				{categoryName}
			</span>
			<button 
				class="text-text-sec-light dark:text-text-sec-dark hover:text-white opacity-0 group-hover:opacity-100 transition-opacity"
				on:click|stopPropagation={() => {}}
			>
				<span class="material-symbols-outlined text-[20px]">more_horiz</span>
			</button>
		</div>

		<!-- Title and Description -->
		<h3 class="text-text-main-light dark:text-text-main-dark font-bold text-lg mb-1 group-hover:text-primary transition-colors cursor-pointer">
			{board.title || 'Untitled Board'}
		</h3>
		<p class="text-text-sec-light dark:text-text-sec-dark text-sm line-clamp-2 mb-4">
			{board.description || 'No description available'}
		</p>

		<!-- Progress Section -->
		<div class="mt-auto flex flex-col gap-3">
			<div class="flex flex-col gap-1">
				<div class="flex justify-between text-xs text-text-sec-light dark:text-text-sec-dark">
					<span>Progreso</span>
					<span>{progress}%</span>
				</div>
				<div class="progress-bar">
					<div class="progress-fill {categoryColor.progress}" style="width: {progress}%"></div>
				</div>
			</div>

			<!-- Meta Information -->
			<div class="flex items-center justify-between border-t border-border-light dark:border-border-dark pt-3 mt-1">
				<!-- Team Members -->
				<div class="avatar-group">
					{#each teamMembers.slice(0, 2) as member (member.id)}
						<img 
							alt={member.name} 
							class="avatar avatar-sm ring-2 ring-white dark:ring-surface-dark object-cover" 
							src={member.avatar}
						/>
					{/each}
					{#if teamMembers.length > 2}
						<div class="flex items-center justify-center avatar avatar-sm ring-2 ring-white dark:ring-surface-dark bg-slate-100 dark:bg-surface-dark text-[10px] font-medium text-text-sec-light dark:text-text-sec-dark">
							+{teamMembers.length - 2}
						</div>
					{/if}
				</div>

				<!-- Time -->
				<div class="flex items-center gap-1 text-text-sec-light dark:text-text-sec-dark text-xs">
					<span class="material-symbols-outlined text-[14px]">calendar_today</span>
					<span>{relativeTime}</span>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	.line-clamp-2 {
		display: -webkit-box;
		-webkit-line-clamp: 2;
		line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}
</style>