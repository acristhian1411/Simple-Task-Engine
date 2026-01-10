import { http } from './http.js';

export async function getBoards(params = {}) {
	const res = await http.get('/boards', { params });
	return res.data;
}

export async function getBoardsWithLists(params = {}) {
	const res = await http.get('/lists-with-tasks', { params });
	return res.data;
}
