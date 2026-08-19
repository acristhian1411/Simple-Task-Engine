import { http } from './http.js';

export async function getComponents(params = {}) {
  const res = await http.get('/components', { params });
  return res.data;
}

export async function getComponent(id) {
  const res = await http.get(`/components/${id}`);
  return res.data;
}

export async function createComponent(data) {
  const res = await http.post('/components', data);
  return res.data;
}

export async function updateComponent(id, data) {
  const res = await http.put(`/components/${id}`, data);
  return res.data;
}

export async function deleteComponent(id) {
  const res = await http.delete(`/components/${id}`);
  return res.data;
}

export async function getComponentTestCases(id) {
  const res = await http.get(`/components/${id}/test-cases`);
  return res.data;
}
