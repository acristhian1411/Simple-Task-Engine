import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL;

/**
 * Normaliza respuestas de la API a un array plano.
 * Soportes:
 *  - `{ data: [...] }`
 *  - `{ data: { data: [...], links, meta } }` (paginado)
 *  - `[...]` directo
 */
export function unwrapList(res) {
  if (Array.isArray(res)) return res;
  const data = res?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
}

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
