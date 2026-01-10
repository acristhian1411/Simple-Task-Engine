import { http, setToken } from './http.js';

function extractToken(payload) {
	return payload?.token ?? payload?.data?.token ?? payload?.access_token ?? null;
}

export async function login({ email, password }) {
	const res = await http.post('/auth/login', { email, password });
	const token = extractToken(res.data);
	setToken(token);
	return { token, data: res.data };
}

export async function me() {
	const res = await http.get('/auth/me');
	return res.data;
}

export async function logout() {
	const res = await http.post('/auth/logout');
	setToken(null);
	return res.data;
}
