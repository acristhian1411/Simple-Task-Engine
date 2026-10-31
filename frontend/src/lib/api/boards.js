import { http, unwrapList } from './http.js';

export async function getBoards(params = {}) {
	const res = await http.get('/boards', { params });
	return unwrapList(res);
}

export async function getBoard(id) {
	const res = await http.get(`/boards/${id}`);
	return res.data;
}

export async function createBoard(data) {
	const res = await http.post('/boards', data);
	return res.data;
}

export async function deleteBoard(id) {
	const res = await http.delete(`/boards/${id}`);
	return res.data;
}

export async function getBoardsWithLists(params = {}) {
	const res = await http.get('/boards-with-lists', { params });
	return unwrapList(res);
}

export async function getBoardMembers(id) {
	const res = await http.get(`/boards/${id}/members`);
	return unwrapList(res);
}

export async function addBoardMember(id, email) {
	const res = await http.post(`/boards/${id}/members`, { email });
	return unwrapList(res);
}

export async function removeBoardMember(id, userId) {
	const res = await http.delete(`/boards/${id}/members/${userId}`);
	return unwrapList(res);
}