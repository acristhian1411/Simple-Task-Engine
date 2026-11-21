import { http, getAllPages } from './http.js';

export async function getRecordings(params = {}) {
  const res = await http.get('/recordings', { params });
  return res.data;
}

export function getAllRecordings() {
  return getAllPages(getRecordings);
}

export async function getRecordingLogs(id) {
  const res = await http.get(`/recordings/${id}/logs`);
  return res.data;
}

export async function getRecordingFile(id) {
  const res = await http.get(`/recordings/${id}/file`, { responseType: 'blob' });
  return res.data;
}

export async function updateRecording(id, data) {
  const res = await http.put(`/recordings/${id}`, data);
  return res.data;
}

export async function attachRecording(id, bugId) {
  const res = await http.post(`/recordings/${id}/attach`, { recordable_type: 'bug', recordable_id: bugId });
  return res.data;
}

export async function detachRecording(id) {
  const res = await http.post(`/recordings/${id}/detach`);
  return res.data;
}

export async function deleteRecording(id) {
  const res = await http.delete(`/recordings/${id}`);
  return res.data;
}

export async function getExtensionTokens() {
  const res = await http.get('/extension-tokens');
  return res.data;
}

export async function issueExtensionToken(label) {
  const res = await http.post('/extension-tokens/issue', { label });
  return res.data;
}

export async function revokeExtensionToken(id) {
  const res = await http.post(`/extension-tokens/${id}/revoke`);
  return res.data;
}
