<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { KeyRound, Pencil, Plus, Trash2, Users, X } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import {
    createUser,
    deactivateUser,
    getUsers,
    resetUserPassword,
    updateUser,
  } from "$lib/api/users.js";

  let users = $state([]);
  let loading = $state(true);
  let saving = $state(false);
  let error = $state("");
  let formError = $state("");
  let admin = $state(null);
  let mode = $state(null);
  let selectedUser = $state(null);
  let form = $state({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });

  async function loadUsers() {
    loading = true;
    error = "";
    try {
      users = await getUsers();
    } catch (e) {
      error =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "No se pudieron cargar los miembros.";
    } finally {
      loading = false;
    }
  }

  function openCreate() {
    selectedUser = null;
    form = { name: "", email: "", password: "", password_confirmation: "" };
    formError = "";
    mode = "create";
  }

  function openEdit(user) {
    selectedUser = user;
    form = { name: user.name, email: user.email, password: "", password_confirmation: "" };
    formError = "";
    mode = "edit";
  }

  function openReset(user) {
    selectedUser = user;
    form = { name: "", email: "", password: "", password_confirmation: "" };
    formError = "";
    mode = "reset";
  }

  async function save(event) {
    event.preventDefault();
    saving = true;
    formError = "";
    try {
      if (mode === "create") {
        await createUser(form);
      } else if (mode === "edit") {
        await updateUser(selectedUser.id, { name: form.name, email: form.email });
      } else {
        await resetUserPassword(selectedUser.id, {
          password: form.password,
          password_confirmation: form.password_confirmation,
        });
      }
      mode = null;
      await loadUsers();
    } catch (e) {
      formError =
        e?.response?.data?.message ??
        e?.response?.data?.error ??
        e?.message ??
        "No se pudo guardar.";
    } finally {
      saving = false;
    }
  }

  async function deactivate(user) {
    if (!confirm(`¿Desactivar la cuenta de ${user.name}?`)) return;
    error = "";
    try {
      await deactivateUser(user.id);
      await loadUsers();
    } catch (e) {
      error =
        e?.response?.data?.error ??
        e?.response?.data?.message ??
        e?.message ??
        "No se pudo desactivar la cuenta.";
    }
  }

  onMount(async () => {
    admin = await refreshMe();
    if (!admin) return goto("/login");
    if (!admin.is_admin) {
      error = "No tienes permisos para gestionar miembros.";
      loading = false;
      return;
    }
    await loadUsers();
  });
</script>

<svelte:head>
  <title>Miembros - Maius Kanban</title>
</svelte:head>

<div class="p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">
        Miembros
      </h1>
      <p class="mt-1 text-sm text-text-sec-light dark:text-text-sec-dark">
        Administra las cuentas que pueden acceder al sistema.
      </p>
    </div>
    {#if admin?.is_admin}
      <button
        type="button"
        onclick={openCreate}
        class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white transition-colors hover:bg-indigo-700"
      >
        <Plus size={18} />
        Crear usuario
      </button>
    {/if}
  </div>

  {#if mode}
    <section
      class="space-y-4 rounded-xl border border-border-light bg-surface-light p-5 dark:border-border-dark dark:bg-surface-dark"
      aria-labelledby="member-form-title"
    >
      <div class="flex items-center justify-between gap-3">
        <h2 id="member-form-title" class="text-lg font-semibold text-text-main-light dark:text-text-main-dark">
          {mode === "create"
            ? "Crear usuario"
            : mode === "edit"
              ? "Editar usuario"
              : `Restablecer contraseña de ${selectedUser.name}`}
        </h2>
        <button type="button" onclick={() => (mode = null)} aria-label="Cerrar formulario">
          <X size={20} />
        </button>
      </div>
      <form onsubmit={save} class="grid gap-4 md:grid-cols-2">
        {#if mode !== "reset"}
          <label class="space-y-1 text-sm text-text-sec-light dark:text-text-sec-dark">
            Nombre
            <input class="form-input" bind:value={form.name} required maxlength="255" autocomplete="name" />
          </label>
          <label class="space-y-1 text-sm text-text-sec-light dark:text-text-sec-dark">
            Correo electrónico
            <input class="form-input" type="email" bind:value={form.email} required maxlength="255" autocomplete="email" />
          </label>
        {/if}
        {#if mode === "create" || mode === "reset"}
          <label class="space-y-1 text-sm text-text-sec-light dark:text-text-sec-dark">
            Nueva contraseña
            <input class="form-input" type="password" bind:value={form.password} required minlength="8" autocomplete="new-password" />
          </label>
          <label class="space-y-1 text-sm text-text-sec-light dark:text-text-sec-dark">
            Confirmar contraseña
            <input class="form-input" type="password" bind:value={form.password_confirmation} required minlength="8" autocomplete="new-password" />
          </label>
        {/if}
        {#if formError}
          <p class="text-sm text-red-500 md:col-span-2" role="alert">{formError}</p>
        {/if}
        <div class="flex justify-end gap-2 md:col-span-2">
          <button
            type="button"
            onclick={() => (mode = null)}
            class="rounded-lg border border-border-light px-4 py-2 text-text-main-light dark:border-border-dark dark:text-text-main-dark"
          >
            Cancelar
          </button>
          <button
            type="submit"
            disabled={saving}
            class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {saving ? "Guardando..." : mode === "reset" ? "Restablecer contraseña" : "Guardar"}
          </button>
        </div>
      </form>
    </section>
  {/if}

  {#if error}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-500" role="alert">
      {error}
    </div>
  {/if}

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando miembros...</p>
  {:else if !error || users.length}
    <div class="overflow-x-auto rounded-xl border border-border-light bg-surface-light dark:border-border-dark dark:bg-surface-dark">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border-light bg-slate-50 text-left text-text-sec-light dark:border-border-dark dark:bg-slate-900/50 dark:text-text-sec-dark">
            <th class="px-4 py-3 font-medium">Nombre</th>
            <th class="px-4 py-3 font-medium">Correo</th>
            <th class="px-4 py-3 font-medium">Acceso</th>
            <th class="px-4 py-3 text-right font-medium">Acciones</th>
          </tr>
        </thead>
        <tbody>
          {#each users as user (user.id)}
            <tr class="border-b border-border-light last:border-0 dark:border-border-dark">
              <td class="px-4 py-3 font-medium text-text-main-light dark:text-text-main-dark">{user.name}</td>
              <td class="px-4 py-3 text-text-sec-light dark:text-text-sec-dark">{user.email}</td>
              <td class="px-4 py-3">
                {#if user.is_admin}
                  <span class="rounded-full bg-indigo-500/10 px-2.5 py-1 text-xs font-medium text-indigo-500">Admin</span>
                {:else}
                  <span class="text-text-sec-light dark:text-text-sec-dark">Miembro</span>
                {/if}
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end gap-1">
                  <button type="button" onclick={() => openEdit(user)} class="rounded-lg p-2 text-text-sec-light hover:bg-indigo-500/10 hover:text-indigo-500" title="Editar" aria-label={`Editar ${user.name}`}>
                    <Pencil size={17} />
                  </button>
                  <button type="button" onclick={() => openReset(user)} class="rounded-lg p-2 text-text-sec-light hover:bg-indigo-500/10 hover:text-indigo-500" title="Restablecer contraseña" aria-label={`Restablecer contraseña de ${user.name}`}>
                    <KeyRound size={17} />
                  </button>
                  {#if user.id !== admin?.id}
                    <button type="button" onclick={() => deactivate(user)} class="rounded-lg p-2 text-text-sec-light hover:bg-red-500/10 hover:text-red-500" title="Desactivar" aria-label={`Desactivar ${user.name}`}>
                      <Trash2 size={17} />
                    </button>
                  {/if}
                </div>
              </td>
            </tr>
          {:else}
            <tr>
              <td colspan="4" class="px-4 py-12 text-center text-text-sec-light dark:text-text-sec-dark">
                <Users size={26} class="mx-auto mb-2" />
                No hay usuarios para mostrar.
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div>
