import { http, unwrapList } from './http.js';

export async function getTasks(params = {}) {
  const res = await http.get('/tasks', { params });
  return unwrapList(res);
}

export async function getTask(id) {
  const res = await http.get(`/tasks/${id}`);
  return res.data;
}

export async function createTask(data) {
  const res = await http.post('/tasks', data);
  return res.data;
}

export async function updateTask(id, data) {
  const res = await http.put(`/tasks/${id}`, data);
  return res.data;
}

export async function deleteTask(id) {
  const res = await http.delete(`/tasks/${id}`);
  return res.data;
}

export async function getBlockedBy(id) {
  const res = await http.get(`/tasks/${id}/blocked-by`);
  return unwrapList(res);
}

export async function attachTaskComponent(taskId, componentId) {
  const res = await http.post(`/tasks/${taskId}/components`, { component_id: componentId });
  return res.data;
}

export async function detachTaskComponent(taskId, componentId) {
  const res = await http.delete(`/tasks/${taskId}/components/${componentId}`);
  return res.data;
}

export async function attachTaskBug(taskId, bugId, relationType = 'related') {
  const res = await http.post(`/tasks/${taskId}/bugs`, { bug_id: bugId, relation_type: relationType });
  return res.data;
}

export async function detachTaskBug(taskId, bugId) {
  const res = await http.delete(`/tasks/${taskId}/bugs/${bugId}`);
  return res.data;
}
