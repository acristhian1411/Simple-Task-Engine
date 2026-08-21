import { http, unwrapList } from './http.js';

export async function getSubtasks(params = {}) {
  const res = await http.get('/subtasks', { params });
  return unwrapList(res);
}

export async function getTaskSubtasks(taskId) {
  const res = await http.get(`/tasks/${taskId}/subtasks`);
  return unwrapList(res);
}

export async function createSubtask(data) {
  const res = await http.post('/subtasks', data);
  return res.data;
}

export async function updateSubtask(id, data) {
  const res = await http.put(`/subtasks/${id}`, data);
  return res.data;
}

export async function deleteSubtask(id) {
  const res = await http.delete(`/subtasks/${id}`);
  return res.data;
}
