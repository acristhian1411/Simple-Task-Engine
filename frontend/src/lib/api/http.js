import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL;

export function getToken() {
	if (typeof window === 'undefined') return null;
	return localStorage.getItem('token');
}

export function setToken(token) {
	if (typeof window === 'undefined') return;
	if (!token) {
		localStorage.removeItem('token');
		return;
	}
	localStorage.setItem('token', token);
}

export const http = axios.create({
	baseURL
});

http.interceptors.request.use((config) => {
	const token = getToken();
	if (token) {
		config.headers = config.headers ?? {};
		config.headers.Authorization = `Bearer ${token}`;
	}
	return config;
});
