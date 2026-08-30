export const auditSidebar = $state({
  open: false,
  auditableId: null,
  auditableType: '',
});

export function openAuditSidebar(auditableId, auditableType) {
  auditSidebar.open = true;
  auditSidebar.auditableId = auditableId;
  auditSidebar.auditableType = auditableType;
}

export function closeAuditSidebar() {
  auditSidebar.open = false;
}
