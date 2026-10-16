<script>
  import { ChevronLeft, ChevronRight } from "lucide-svelte";

  let { currentPage, lastPage, perPage, total, onPageChange, onPerPageChange } =
    $props();

  let firstResult = $derived(total === 0 ? 0 : (currentPage - 1) * perPage + 1);
  let lastResult = $derived(Math.min(currentPage * perPage, total));
</script>

<nav
  aria-label="Paginación"
  class="flex flex-wrap items-center justify-between gap-3 border-t border-border-light py-3 text-sm text-text-sec-light dark:border-border-dark dark:text-text-sec-dark"
>
  <span>Mostrando {firstResult}-{lastResult} de {total}</span>

  <div class="flex items-center gap-2">
    <label for="page-size" class="sr-only">Resultados por página</label>
    <select
      id="page-size"
      class="form-input w-auto py-1.5"
      value={perPage}
      aria-label="Resultados por página"
      onchange={(event) => onPerPageChange(Number(event.currentTarget.value))}
    >
      {#each [10, 25, 50, 100] as size}
        <option value={size}>{size} por página</option>
      {/each}
    </select>

    <button
      type="button"
      class="rounded-md border border-border-light p-1.5 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-border-dark dark:hover:bg-slate-800"
      aria-label="Página anterior"
      title="Página anterior"
      disabled={currentPage <= 1}
      onclick={() => onPageChange(currentPage - 1)}
    >
      <ChevronLeft size={18} />
    </button>
    <span class="min-w-20 text-center">Página {currentPage} de {lastPage}</span>
    <button
      type="button"
      class="rounded-md border border-border-light p-1.5 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-border-dark dark:hover:bg-slate-800"
      aria-label="Página siguiente"
      title="Página siguiente"
      disabled={currentPage >= lastPage}
      onclick={() => onPageChange(currentPage + 1)}
    >
      <ChevronRight size={18} />
    </button>
  </div>
</nav>
