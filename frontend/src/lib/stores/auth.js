import { writable } from 'svelte/store';
import { login as apiLogin, me as apiMe, logout as apiLogout } from '$lib/api/auth.js';
import { getToken, setToken } from '$lib/api/http.js';

export const auth = writable({
	user: null,
	token: getToken(),
	loading: false,
	error: null
});

export async function login(email, password) {
	auth.update((s) => ({ ...s, loading: true, error: null }));
	try {
		const { token } = await apiLogin({ email, password });
		auth.update((s) => ({ ...s, token, loading: false }));
		await refreshMe();
	} catch (e) {
		setToken(null);
		auth.update((s) => ({ ...s, user: null, token: null, loading: false, error: e?.message ?? 'Login failed' }));
		throw e;
	}
}

export async function refreshMe() {
	const token = getToken();
	if (!token) {
		auth.update((s) => ({ ...s, user: null, token: null }));
		return null;
	}
	try {
		const data = await apiMe();
		const user = data?.data ?? data;
		auth.update((s) => ({ ...s, user, token }));
		return user;
	} catch (e) {
		setToken(null);
		auth.update((s) => ({ ...s, user: null, token: null }));
		return null;
	}
}

export async function logout() {
	auth.update((s) => ({ ...s, loading: true, error: null }));
	try {
		await apiLogout();
	} finally {
		setToken(null);
		auth.set({ user: null, token: null, loading: false, error: null });
	}
}
