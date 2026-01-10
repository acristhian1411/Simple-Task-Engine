<script>
	import { login } from '$lib/stores/auth.js';
	import { goto } from '$app/navigation';

	let email = '';
	let password = '';
	let error = '';
	let loading = false;

	async function submit() {
		error = '';
		loading = true;
		try {
			await login(email, password);
			goto('/tableros');
		} catch (e) {
			error = e?.response?.data?.message ?? e?.message ?? 'Login failed';
		} finally {
			loading = false;
		}
	}
</script>

<div class="container-page flex items-center justify-center px-4">
	<div class="card">
		<h1 class="text-2xl font-bold">Login</h1>
		<p class="mt-1 text-sm text-slate-300">Ingresá con tu email y contraseña</p>

		<form class="mt-6 space-y-4" on:submit|preventDefault={submit}>
			<div>
				<label class="form-label" for="email">Email</label>
				<input id="email" class="input" type="email" bind:value={email} autocomplete="email" required />
			</div>

			<div>
				<label class="form-label" for="password">Password</label>
				<input
					id="password"
					class="input"
					type="password"
					bind:value={password}
					autocomplete="current-password"
					required
				/>
			</div>

			{#if error}
				<div class="alert-error">{error}</div>
			{/if}

			<button class="btn-primary w-full" type="submit" disabled={loading}>
				{loading ? 'Ingresando...' : 'Ingresar'}
			</button>
		</form>
	</div>
</div>
