import { http } from './http.js';

export async function getAuditsFor(auditableId, auditableType) {
  const res = await http.get(`/audits/auditable/${auditableId}`, {
    params: { auditable_type: auditableType },
  });
  return res.data;
}
