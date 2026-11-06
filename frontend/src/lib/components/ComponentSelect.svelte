<script>
  import SearchSelect from "./SearchSelect.svelte";

  export let components = [];
  export let value = "";
  export let excludeId = null;
  export let inputId = "component_id";
  export let placeholder = "Buscar por nombre o ID...";
  export let clearLabel = "Quitar componente";
  export let clearable = true;
  // Se llama con el nuevo valor cada vez que cambia la selección
  export let onchange = () => {};
</script>

<SearchSelect
  items={components}
  bind:value
  {excludeId}
  {inputId}
  {placeholder}
  {clearLabel}
  {clearable}
  {onchange}
  emptyText="No se encontraron componentes."
/>
