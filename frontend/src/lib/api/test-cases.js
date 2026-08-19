import { http } from './http.js';

export async function getTestCases(params = {}) {
  const res = await http.get('/test-cases', { params });
  return res.data;
}

export async function getTestCase(id) {
  const res = await http.get(`/test-cases/${id}`);
  return res.data;
}

export async function createTestCase(data) {
  const res = await http.post('/test-cases', data);
  return res.data;
}

export async function updateTestCase(id, data) {
  const res = await http.put(`/test-cases/${id}`, data);
  return res.data;
}

export async function deleteTestCase(id) {
  const res = await http.delete(`/test-cases/${id}`);
  return res.data;
}

export async function getTestSteps(testCaseId) {
  const res = await http.get(`/test-cases/${testCaseId}/test-steps`);
  return res.data;
}

export async function getActors(testCaseId) {
  const res = await http.get(`/test-cases/${testCaseId}/actors`);
  return res.data;
}
