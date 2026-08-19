<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Save } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { createComponent } from "$lib/api/components.js";

  let loading = $state(true);
  let error = $state("");
  let submitting = $state(false);
  let form = $state({ name: "", description: "" });

  onMount(async () => {
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message;
    } finally {
      loading = false;
    }
  });

  async function handleSubmit() {
    if (!form.name) {
      error = "El nombre es obligatorio";
      return;
    }
    submitting = true;
    error = "";
    try {
      await createComponent({ name: form.name, description: form.description || null });
      goto("/components");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al guardar";
    } finally {
      submitting = false;
    }
  }
</script>

<div class="max-w-2xl mx-auto p-6 space-y-6">
  <div class="flex items-center gap-4">
    <a href="/components" class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
      <ArrowLeft size={20} />
    </a>
    <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Crear Componente</h1>
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else}
    <form
      onsubmit={(e) => {
        e.preventDefault();
        handleSubmit();
      }}
      class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6"
    >
      <div class="space-y-2">
        <label for="name" class="block text-sm font-medium">Nombre del componente</label>
        <input type="text" id="name" required placeholder="ej. Autenticación, Procesamiento de pagos" bind:value={form.name} class="form-input" />
      </div>

      <div class="space-y-2">
        <label for="description" class="block text-sm font-medium">Descripción</label>
        <textarea id="description" rows="4" placeholder="Describí el propósito de este componente..." bind:value={form.description} class="form-input resize-none"></textarea>
      </div>

      {#if error}
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
      {/if}

      <div class="flex justify-end pt-4">
        <button
          type="submit"
          disabled={submitting}
          class="flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-lg font-medium transition-colors"
        >
          <Save size={18} />
          <span>{submitting ? "Guardando..." : "Crear Componente"}</span>
        </button>
      </div>
    </form>
  {/if}
</div>
