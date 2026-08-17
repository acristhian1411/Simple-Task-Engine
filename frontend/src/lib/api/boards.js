import { http } from './http.js';

export async function getBoards(params = {}) {
	const res = await http.get('/boards', { params });
	return res.data;
}

export async function getBoard(id) {
	const res = await http.get(`/boards/${id}`);
	return res.data;
}

export async function createBoard(data) {
	const res = await http.post('/boards', data);
	return res.data;
}

export async function getBoardsWithLists(params = {}) {
	const res = await http.get('/boards-with-lists', { params });
	return res.data;
}