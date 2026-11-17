<script>
  import SearchSelect from "$lib/components/SearchSelect.svelte";

  // Miembros del tablero tal como los devuelve GET /boards/{id}/members
  export let members = [];
  // Id del responsable como string ("" = sin responsable)
  export let value = "";
  export let inputId = "assignee_select";
  export let onchange = () => {};
</script>

<SearchSelect
  items={members}
  bind:value
  {inputId}
  {onchange}
  placeholder="Sin responsable, buscá un miembro..."
  clearLabel="Quitar responsable"
  emptyText="No hay miembros con ese nombre."
  getLabel={(member) => member.name || member.email}
  getDetail={(member) => member.email}
/>
