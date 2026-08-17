import { http } from './http.js';

export async function getListsWithTasks(params = {}) {
  const res = await http.get('/lists-with-tasks', { params });
  return res.data;
}