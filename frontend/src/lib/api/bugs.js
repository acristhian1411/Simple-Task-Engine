import { http } from './http.js';

export async function getBugs(params = {}) {
  const res = await http.get('/bugs', { params });
  return res.data;
}

export async function getBug(id) {
  const res = await http.get(`/bugs/${id}`);
  return res.data;
}

export async function createBug(data) {
  const res = await http.post('/bugs', data);
  return res.data;
}

export async function updateBug(id, data) {
  const res = await http.put(`/bugs/${id}`, data);
  return res.data;
}

export async function deleteBug(id) {
  const res = await http.delete(`/bugs/${id}`);
  return res.data;
}
