import { http, unwrapList } from './http.js';

export async function getListsWithTasks(params = {}) {
  const res = await http.get('/lists-with-tasks', { params });
  return unwrapList(res);
}

export async function createList(data) {
  const res = await http.post('/lists', data);
  return res.data;
}

export async function updateList(id, data) {
  const res = await http.put(`/lists/${id}`, data);
  return res.data;
}

export async function deleteList(id) {
  const res = await http.delete(`/lists/${id}`);
  return res.data;
}
