<script>
  import { MessageSquare, Send, Trash2, Loader2 } from "lucide-svelte";
  import { auth } from "$lib/stores/auth.js";
  import { getCommentsFor, createComment, deleteComment } from "$lib/api/comments.js";

  let { refId, refTable } = $props();

  let comments = $state([]);
  let newComment = $state("");
  let loading = $state(true);
  let submitting = $state(false);
  let error = $state("");

  let currentUserId = $derived($auth.user?.id ?? null);

  async function loadComments() {
    loading = true;
    error = "";
    try {
      const res = await getCommentsFor(refTable, refId);
      comments = Array.isArray(res) ? res : res?.data ?? [];
    } catch (err) {
      error = err?.response?.data?.error ?? err?.message ?? "No se pudieron cargar los comentarios";
    } finally {
      loading = false;
    }
  }

  async function addComment() {
    const content = newComment.trim();
    if (!content || submitting) return;

    submitting = true;
    error = "";
    try {
      const created = await createComment({ content, refTable, refId });
      comments = [{ ...created, user: $auth.user }, ...comments];
      newComment = "";
    } catch (err) {
      error = err?.response?.data?.error ?? err?.message ?? "No se pudo guardar el comentario";
    } finally {
      submitting = false;
    }
  }

  async function removeComment(comment) {
    const previous = comments;
    comments = comments.filter((c) => c.id !== comment.id);
    try {
      await deleteComment(comment.id);
    } catch (err) {
      comments = previous;
      error = err?.response?.data?.error ?? err?.message ?? "No se pudo eliminar el comentario";
    }
  }

  function formatDate(date) {
    if (!date) return "";
    return new Date(date).toLocaleString("es-ES", {
      day: "2-digit",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function handleKeydown(e) {
    if (e.key === "Enter" && (e.metaKey || e.ctrlKey)) {
      addComment();
    }
  }

  $effect(() => {
    refId;
    refTable;
    loadComments();
  });
</script>

<div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-5">
  <div class="flex items-center gap-2 border-b border-border-light dark:border-border-dark pb-3">
    <MessageSquare size={18} class="text-indigo-500" />
    <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark">Comentarios</h2>
    {#if comments.length > 0}
      <span class="text-xs text-text-sec-light dark:text-text-sec-dark bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
        {comments.length}
      </span>
    {/if}
  </div>

  <div class="flex gap-3">
    <textarea
      bind:value={newComment}
      onkeydown={handleKeydown}
      placeholder="Escribe un comentario... (Ctrl+Enter para enviar)"
      rows="2"
      class="form-input flex-1 resize-none"
    ></textarea>
    <button
      type="button"
      onclick={addComment}
      disabled={submitting || !newComment.trim()}
      class="self-end flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg font-medium transition-colors text-sm shrink-0"
    >
      {#if submitting}
        <Loader2 size={16} class="animate-spin" />
      {:else}
        <Send size={16} />
      {/if}
      <span>Enviar</span>
    </button>
  </div>

  {#if error}
    <p class="text-xs text-red-500">{error}</p>
  {/if}

  {#if loading}
    <div class="flex items-center gap-2 text-sm text-text-sec-light dark:text-text-sec-dark py-4">
      <Loader2 size={16} class="animate-spin" />
      <span>Cargando comentarios...</span>
    </div>
  {:else if comments.length === 0}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark py-2">Todavía no hay comentarios.</p>
  {:else}
    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
      {#each comments as comment (comment.id)}
        <div class="flex gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-border-light dark:border-border-dark group">
          <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-xs font-bold text-white shrink-0">
            {(comment.user?.name ?? "?").slice(0, 2).toUpperCase()}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <span class="text-sm font-medium text-text-main-light dark:text-text-main-dark">
                {comment.user?.name ?? "Usuario eliminado"}
              </span>
              <span class="text-xs text-text-sec-light dark:text-text-sec-dark shrink-0">
                {formatDate(comment.created_at)}
              </span>
            </div>
            <p class="text-sm text-text-sec-light dark:text-text-sec-dark mt-1 whitespace-pre-wrap break-words">
              {comment.content}
            </p>
          </div>
          {#if comment.user_id === currentUserId}
            <button
              type="button"
              onclick={() => removeComment(comment)}
              class="p-1.5 h-fit text-text-sec-light dark:text-text-sec-dark hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
            >
              <Trash2 size={14} />
            </button>
          {/if}
        </div>
      {/each}
    </div>
  {/if}
</div>
