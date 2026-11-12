<script>
  export let items = [];
  export let value = "";
  export let excludeId = null;
  export let inputId = "search_select";
  export let placeholder = "Buscar por nombre o ID...";
  export let clearLabel = "Quitar selección";
  export let clearable = true;
  export let emptyText = "No se encontraron resultados.";
  // Texto principal de cada opción, también es lo que se busca
  export let getLabel = (item) => item.name ?? `#${item.id}`;
  // Texto chico a la derecha de cada opción
  export let getDetail = (item) => `#${item.id}`;
  // Se llama con el nuevo valor cada vez que cambia la selección
  export let onchange = () => {};

  let search = "";
  let open = false;

  $: selectedItem = items.find((item) => String(item.id) === String(value));
  $: if (selectedItem && !open) {
    search = getLabel(selectedItem);
  }
  // Si la página vacía el valor (ej. después de agregar), el texto también se borra
  $: if (!value && !open) {
    search = "";
  }
  $: filteredItems = items
    .filter((item) => String(item.id) !== String(excludeId))
    .filter((item) => {
      const query = search.trim().toLowerCase();
      return (
        !query ||
        getLabel(item)?.toLowerCase().includes(query) ||
        String(item.id).includes(query)
      );
    });

  function setValue(newValue) {
    if (String(newValue) === String(value)) return;
    value = newValue;
    onchange(newValue);
  }

  function handleInput(event) {
    search = event.currentTarget.value;
    open = true;
    if (
      selectedItem &&
      search.trim().toLowerCase() !== getLabel(selectedItem).toLowerCase()
    ) {
      setValue("");
    }
  }

  function selectItem(item) {
    setValue(String(item.id));
    search = getLabel(item);
    open = false;
  }

  function clearSelection() {
    setValue("");
    search = "";
    open = false;
  }

  function closeOptions() {
    setTimeout(() => {
      open = false;
      if (selectedItem) {
        search = getLabel(selectedItem);
      }
    }, 100);
  }
</script>

<div class="relative">
  <div class="relative">
    <input
      type="search"
      id={inputId}
      value={search}
      oninput={handleInput}
      onfocus={() => (open = true)}
      onblur={closeOptions}
      {placeholder}
      autocomplete="off"
      class="form-input pr-9"
      aria-autocomplete="list"
      aria-controls="{inputId}-options"
      aria-expanded={open}
    />
    {#if clearable && value}
      <button
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-text-sec-light hover:bg-slate-100 dark:text-text-sec-dark dark:hover:bg-slate-800"
        onclick={clearSelection}
        aria-label={clearLabel}
        title={clearLabel}
      >
        ×
      </button>
    {/if}
  </div>

  {#if open}
    <div
      id="{inputId}-options"
      role="listbox"
      class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-border-light bg-surface-light p-1 shadow-lg dark:border-border-dark dark:bg-surface-dark"
    >
      {#if filteredItems.length > 0}
        {#each filteredItems as item}
          <button
            type="button"
            role="option"
            aria-selected={String(item.id) === String(value)}
            class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-sm text-text-main-light hover:bg-slate-100 dark:text-text-main-dark dark:hover:bg-slate-800"
            onmousedown={(event) => {
              event.preventDefault();
              selectItem(item);
            }}
          >
            <span>{getLabel(item)}</span>
            <span class="shrink-0 text-xs text-text-sec-light dark:text-text-sec-dark">{getDetail(item)}</span>
          </button>
        {/each}
      {:else}
        <p class="px-3 py-2 text-sm text-text-sec-light dark:text-text-sec-dark">
          {emptyText}
        </p>
      {/if}
    </div>
  {/if}
</div>
