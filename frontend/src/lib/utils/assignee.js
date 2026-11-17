// Único lugar del front que sabe cómo llega y cómo se manda el responsable de una tarea.
// La API devuelve `assignee` ({ id, name, email } o null) y recibe `assignee_id`.
// Si se pasa a varios responsables, se cambia acá (y AssigneeSelect), no en cada componente.

/** Responsables de la tarea, siempre como array. */
export function getTaskAssignees(task) {
  if (Array.isArray(task?.assignees)) return task.assignees;
  return task?.assignee ? [task.assignee] : [];
}

/** Id del responsable actual o "" si no tiene (formato que usa el selector). */
export function getAssigneeValue(task) {
  return task?.assignee?.id != null ? String(task.assignee.id) : "";
}

export function isAssignedTo(task, userId) {
  if (userId == null) return false;
  return getTaskAssignees(task).some((u) => String(u.id) === String(userId));
}

/** Datos para crear/actualizar la tarea a partir del valor del selector ("" = sin responsable). */
export function assigneePayload(value) {
  return { assignee_id: value === "" || value == null ? null : Number(value) };
}

/** Mensaje de error de la API al asignar, si lo hay. */
export function assigneeError(e) {
  return e?.response?.data?.errors?.assignee_id?.[0] ?? null;
}
