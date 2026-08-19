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

export async function getComponentChildren(id) {
  const res = await http.get(`/components/${id}/children`);
  return res.data;
}

export async function getComponentDependencies(id) {
  const res = await http.get(`/components/${id}/dependencies`);
  return res.data;
}

export async function getComponentDependents(id) {
  const res = await http.get(`/components/${id}/dependents`);
  return res.data;
}

export async function getComponentImpact(id) {
  const res = await http.get(`/components/${id}/impact`);
  return res.data;
}

export async function attachComponentDependency(id, data) {
  const res = await http.post(`/components/${id}/dependencies`, data);
  return res.data;
}

export async function detachComponentDependency(id, dependsOnId) {
  const res = await http.delete(`/components/${id}/dependencies/${dependsOnId}`);
  return res.data;
}

export async function getComponentsTree(params = {}) {
  const res = await http.get('/components/tree', { params });
  return res.data;
}
