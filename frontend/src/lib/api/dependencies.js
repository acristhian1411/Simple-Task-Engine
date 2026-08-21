import { http, unwrapList } from './http.js';

export async function getDependencies(params = {}) {
  const res = await http.get('/task-dependencies', { params });
  return unwrapList(res);
}

export async function getTaskDependencies(taskId) {
  const res = await http.get(`/tasks/${taskId}/dependencies`);
  return unwrapList(res);
}

export async function createDependency(data) {
  const res = await http.post('/task-dependencies', data);
  return res.data;
}

export async function updateDependency(id, data) {
  const res = await http.put(`/task-dependencies/${id}`, data);
  return res.data;
}

export async function deleteDependency(id) {
  const res = await http.delete(`/task-dependencies/${id}`);
  return res.data;
}
