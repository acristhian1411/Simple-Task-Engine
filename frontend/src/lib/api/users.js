import { http } from "./http.js";

export async function getUsers() {
  const res = await http.get("/users");
  return res.data?.data ?? [];
}

export async function createUser(data) {
  const res = await http.post("/users", data);
  return res.data?.data;
}

export async function updateUser(id, data) {
  const res = await http.put(`/users/${id}`, data);
  return res.data?.data;
}

export async function resetUserPassword(id, data) {
  const res = await http.post(`/users/${id}/reset-password`, data);
  return res.data;
}

export async function deactivateUser(id) {
  const res = await http.delete(`/users/${id}`);
  return res.data;
}
