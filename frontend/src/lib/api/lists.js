import { http } from './http.js';

export async function getListsByBoard(boardId, params = {}) {
  const res = await http.get(`/boards/${boardId}/lists`, { params });
  return res.data;
}

export async function getTasksByList(listId, params = {}) {
  const res = await http.get(`/lists/${listId}/tasks`, { params });
  return res.data;
}

export async function getTasksByBoard(boardId, params = {}) {
  const res = await http.get(`/boards/${boardId}/tasks`, { params });
  return res.data;
}

export async function getListsWithTasks(params = {}) {
  const res = await http.get('/lists-with-tasks', { params });
  return res.data;
}