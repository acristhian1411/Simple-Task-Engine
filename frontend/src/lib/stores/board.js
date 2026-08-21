import { writable } from 'svelte/store';
import { SHADOW_ITEM_MARKER_PROPERTY_NAME } from 'svelte-dnd-action';
import { getBoard } from '$lib/api/boards.js';
import { getListsWithTasks, createList } from '$lib/api/lists.js';
import { createTask, updateTask, getBlockedBy } from '$lib/api/tasks.js';

function isDoneList(title = '') {
  const t = title.toLowerCase();
  return t === 'done' || t.includes('hecho') || t.includes('complet');
}

// Descarta los "shadow items" que svelte-dnd-action inyecta temporalmente durante el drag
function cleanItems(items) {
  return (items || []).filter(
    (t) => t && !t[SHADOW_ITEM_MARKER_PROPERTY_NAME],
  );
}

function createBoardStore() {
  const { subscribe, set, update } = writable({
    board: null,
    lists: [],
    loading: false,
    error: '',
  });

  function findList(state, listId) {
    return state.lists.find((l) => l.id === listId);
  }

  async function loadBoard(boardId) {
    update((s) => ({ ...s, loading: true, error: '' }));
    try {
      const boardsRes = await getBoard(boardId);
      const board = boardsRes?.data ?? boardsRes;
      if (!board) throw new Error('Tablero no encontrado');
      const lists = await getListsWithTasks({ board_id: boardId, per_page: 100 });
      set({
        board,
        lists: Array.isArray(lists) ? lists : [],
        loading: false,
        error: '',
      });
    } catch (e) {
      update((s) => ({
        ...s,
        loading: false,
        error: e?.response?.data?.error ?? e?.message ?? 'Error cargando tablero',
      }));
    }
  }

  async function addList(title) {
    const current = await new Promise((resolve) => {
      subscribe((s) => resolve(s))();
    });
    if (!current.board) throw new Error('Sin tablero');
    const res = await createList({
      board_id: current.board.id,
      title,
      order: current.lists.length,
    });
    const created = res?.data ?? res;
    update((s) => ({ ...s, lists: [...s.lists, created] }));
    return created;
  }

  async function addTask({ listId, title, description = null, status = 'todo' }) {
    const current = await new Promise((resolve) => {
      subscribe((s) => resolve(s))();
    });
    const list = findList(current, listId);
    if (!list) throw new Error('Lista no encontrada');
    const res = await createTask({
      list_id: listId,
      title,
      description,
      status,
      order: (list.tasks || []).length,
    });
    const created = res?.data ?? res;
    update((s) => ({
      ...s,
      lists: s.lists.map((l) =>
        l.id === listId ? { ...l, tasks: [...(l.tasks || []), created] } : l,
      ),
    }));
    return created;
  }

  // Reemplaza el array de tasks de una lista (usado por el drag & drop en consider/finalize)
  function syncColumnTasks(listId, newTasks) {
    update((s) => ({
      ...s,
      lists: s.lists.map((l) => (l.id === listId ? { ...l, tasks: newTasks } : l)),
    }));
  }

  // Se llama en cada consider de cada columna para reflejar el estado visual del drag
  function handleConsider(listId, detail) {
    if (!detail || !Array.isArray(detail.items)) return;
    syncColumnTasks(listId, cleanItems(detail.items));
  }

  // Se llama en el finalize de la columna DESTINO: persiste el movimiento + valida dependencias
  async function handleFinalize(listId, detail) {
    const draggedId = getDraggedId(detail);
    if (draggedId == null) return;
    const items = cleanItems(detail.items);
    // Solo la zona que contiene el item (destino) persiste
    const isDestination = items.some(
      (t) => String(t.id) === String(draggedId),
    );
    if (!isDestination) return;

    const current = await new Promise((resolve) => {
      subscribe((s) => resolve(s))();
    });
    const toList = findList(current, listId);
    if (!toList) return;

    const task = current.lists
      .flatMap((l) => l.tasks || [])
      .find((t) => String(t.id) === String(draggedId)) || { id: draggedId };

    // Validación: no mover a una lista "done" si hay bloqueadores pendientes
    if (isDoneList(toList.title)) {
      const blockers = await getBlockedBy(draggedId);
      if (Array.isArray(blockers) && blockers.length > 0) {
        const names = blockers.map((b) => b.title).join(', ') || '(dependencias)';
        // Revertir el estado local recargando desde el backend
        await reloadLists(current.board?.id);
        throw new Error(`No se puede completar: depende de "${names}"`);
      }
    }

    const newStatus = isDoneList(toList.title) ? 'done' : task.status || 'todo';
    const newOrder = items.findIndex((t) => String(t.id) === String(draggedId));
    if (newOrder < 0) return;
    try {
      await updateTask(draggedId, { list_id: listId, status: newStatus, order: newOrder });
    } catch (e) {
      await reloadLists(current.board?.id);
      throw e;
    }
    // Reconstruir el estado desde el backend para que el front refleje el movimiento real
    await reloadLists(current.board?.id);
  }

  function getDraggedId(detail) {
    const raw = detail?.info?.id ?? detail?.info?.draggedId ?? detail?.draggedId;
    if (raw == null) return null;
    return String(raw);
  }

  async function reloadLists(boardId) {
    if (!boardId) return;
    try {
      const lists = await getListsWithTasks({ board_id: boardId, per_page: 100 });
      update((s) => ({
        ...s,
        lists: Array.isArray(lists) ? lists : s.lists,
      }));
    } catch {
      // mantener el estado actual si falla
    }
  }

  async function changeTask(taskId, data) {
    const res = await updateTask(taskId, data);
    const updated = res?.data ?? res;
    update((s) => ({
      ...s,
      lists: s.lists.map((l) => ({
        ...l,
        tasks: (l.tasks || []).map((t) => (t.id === taskId ? { ...t, ...updated } : t)),
      })),
    }));
    return updated;
  }

  return {
    subscribe,
    set,
    update,
    loadBoard,
    addList,
    addTask,
    syncColumnTasks,
    handleConsider,
    handleFinalize,
    changeTask,
    reloadLists,
    isDoneList,
  };
}

export const boardStore = createBoardStore();
