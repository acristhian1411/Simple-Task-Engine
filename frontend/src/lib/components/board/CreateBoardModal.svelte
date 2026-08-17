<script>
	import { createEventDispatcher } from 'svelte';
	import Modal from '$lib/components/ui/Modal.svelte';
	import { createBoard } from '$lib/api/boards.js';
	import { goto } from '$app/navigation';

	export let show = false;

	const dispatch = createEventDispatcher();

	let title = '';
	let description = '';
	let error = '';
	let loading = false;

	function close() {
		title = '';
		description = '';
		error = '';
		dispatch('close');
	}

	async function submit() {
		error = '';
		loading = true;
		try {
			const res = await createBoard({ title, description: description || null });
			const board = res?.data ?? res;
			close();
			goto(`/board/${board.id}`);
		} catch (e) {
			error = e?.response?.data?.error ?? e?.message ?? 'Error al crear el tablero';
		} finally {
			loading = false;
		}
	}
</script>

<Modal {show} size="lg" position="center" on:close={close}>
	<div class="p-6">
		<div class="flex items-center justify-between mb-4">
			<h2 class="text-xl font-bold text-gray-900 dark:text-white">Crear nuevo tablero</h2>
			<button
				class="p-1 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
				on:click={close}
				aria-label="Cerrar"
				type="button"
			>
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<form class="space-y-4" on:submit|preventDefault={submit}>
			<div>
				<label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="board-title">
					Título
				</label>
				<input
					id="board-title"
					class="form-input mt-1"
					type="text"
					bind:value={title}
					placeholder="Ej: Sprint de Agosto"
					required
				/>
			</div>

			<div>
				<label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="board-desc">
					Descripción
				</label>
				<textarea
					id="board-desc"
					class="form-input mt-1 resize-none"
					rows="3"
					bind:value={description}
					placeholder="¿De qué trata este tablero?"
				></textarea>
			</div>

			{#if error}
				<div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-600 dark:text-red-300">
					{error}
				</div>
			{/if}

			<div class="flex justify-end gap-2 pt-2">
				<button class="btn-secondary" type="button" on:click={close}>Cancelar</button>
				<button class="btn-primary" type="submit" disabled={loading}>
					{loading ? 'Creando...' : 'Crear tablero'}
				</button>
			</div>
		</form>
	</div>
</Modal>