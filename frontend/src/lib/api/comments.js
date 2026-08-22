import { http } from './http.js';

const MORPH_TYPES = {
  bug: 'App\\Models\\Bugs',
  bugs: 'App\\Models\\Bugs',
  task: 'App\\Models\\Task',
  tasks: 'App\\Models\\Task',
  'test-case': 'App\\Models\\TestCases',
  'test-cases': 'App\\Models\\TestCases',
  test_case: 'App\\Models\\TestCases',
  component: 'App\\Models\\Components',
  components: 'App\\Models\\Components',
};

export function morphType(refTable) {
  return MORPH_TYPES[refTable] ?? refTable;
}

export async function getCommentsFor(type, id) {
  const res = await http.get(`/comments/for/${type}/${id}`);
  return res.data;
}

export async function createComment({ content, refTable, refId }) {
  const res = await http.post('/comments', {
    content,
    commentable_type: morphType(refTable),
    commentable_id: refId,
  });
  return res.data;
}

export async function deleteComment(id) {
  const res = await http.delete(`/comments/${id}`);
  return res.data;
}
