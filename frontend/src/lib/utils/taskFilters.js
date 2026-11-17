import { isAssignedTo } from "$lib/utils/assignee.js";

// Filtros del tablero. Para sumar uno nuevo alcanza con agregar una entrada:
//   id: identificador único
//   label / icon: lo que se muestra en el botón (icon es un Material Symbol)
//   matches(task, ctx): true si la tarea cumple el filtro; ctx trae { userId }
// Con varios filtros activos, la tarea tiene que cumplirlos todos.
// Las tareas que no los cumplen se atenúan en vez de ocultarse, así el drag & drop sigue andando.
export const TASK_FILTERS = [
  {
    id: "mine",
    label: "Mis tareas",
    icon: "person",
    matches: (task, ctx) => isAssignedTo(task, ctx.userId),
  },
];

export function matchesFilters(task, activeIds, ctx) {
  return TASK_FILTERS.filter((f) => activeIds.includes(f.id)).every((f) =>
    f.matches(task, ctx),
  );
}
