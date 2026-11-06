<script>
  export let components = [];
  export let value = "";
  export let excludeId = null;
  export let inputId = "component_id";
  export let placeholder = "Buscar por nombre o ID...";
  export let clearLabel = "Quitar componente";
  export let clearable = true;
  // Se llama con el nuevo valor cada vez que cambia la selección
  export let onchange = () => {};

  let search = "";
  let open = false;

  $: selectedComponent = components.find(
    (component) => String(component.id) === String(value),
  );
  $: if (selectedComponent && !open) {
    search = selectedComponent.name;
  }
  // Si la página vacía el valor (ej. después de agregar), el texto también se borra
  $: if (!value && !open) {
    search = "";
  }
  $: filteredComponents = components
    .filter((component) => String(component.id) !== String(excludeId))
    .filter((component) => {
      const query = search.trim().toLowerCase();
      return (
        !query ||
        component.name?.toLowerCase().includes(query) ||
        String(component.id).includes(query)
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
      selectedComponent &&
      search.trim().toLowerCase() !== selectedComponent.name.toLowerCase()
    ) {
      setValue("");
    }
  }

  function selectComponent(component) {
    setValue(String(component.id));
    search = component.name;
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
      if (selectedComponent) {
        search = selectedComponent.name;
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
      {#if filteredComponents.length > 0}
        {#each filteredComponents as component}
          <button
            type="button"
            role="option"
            aria-selected={String(component.id) === String(value)}
            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-text-main-light hover:bg-slate-100 dark:text-text-main-dark dark:hover:bg-slate-800"
            onmousedown={(event) => {
              event.preventDefault();
              selectComponent(component);
            }}
          >
            <span>{component.name}</span>
            <span class="text-xs text-text-sec-light dark:text-text-sec-dark">#{component.id}</span>
          </button>
        {/each}
      {:else}
        <p class="px-3 py-2 text-sm text-text-sec-light dark:text-text-sec-dark">
          No se encontraron componentes.
        </p>
      {/if}
    </div>
  {/if}
</div>
