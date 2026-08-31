import { http } from './http.js';

export async function getDashboard() {
  const res = await http.get('/dashboard');
  return res.data;
}
