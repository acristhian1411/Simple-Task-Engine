<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Save, Plus, Trash2 } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { createTestCase } from "$lib/api/test-cases.js";
  import { getComponents } from "$lib/api/components.js";

  let components = $state([]);
  let loading = $state(true);
  let error = $state("");
  let submitting = $state(false);

  let form = $state({
    title: "",
    description: "",
    status: "untested",
    preconditions: "",
    postconditions: "",
    expected_result: "",
  });

  let selectedComponentId = $state("");
  let steps = $state([{ step_number: 1, action: "", expected: "", type: "normal" }]);
  let actors = $state([{ actor_name: "" }]);

  function addStep() {
    steps = [...steps, { step_number: steps.length + 1, action: "", expected: "", type: "normal" }];
  }
  function removeStep(index) {
    steps = steps.filter((_, i) => i !== index).map((s, i) => ({ ...s, step_number: i + 1 }));
  }
  function addActor() {
    actors = [...actors, { actor_name: "" }];
  }
  function removeActor(index) {
    actors = actors.filter((_, i) => i !== index);
  }

  onMount(async () => {
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const res = await getComponents({ per_page: 100 });
      components = res?.data ?? res ?? [];
      const params = new URLSearchParams(window.location.search);
      const compId = params.get("componentId");
      if (compId) selectedComponentId = compId;
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando datos";
    } finally {
      loading = false;
    }
  });

  async function handleSubmit() {
    if (!form.title) {
      error = "El título es obligatorio";
      return;
    }
    submitting = true;
    error = "";
    try {
      const payload = {
        title: form.title,
        description: form.description || null,
        component_id: selectedComponentId || null,
        status: form.status,
        preconditions: form.preconditions || null,
        postconditions: form.postconditions || null,
        expected_result: form.expected_result || null,
      };
      const res = await createTestCase(payload);
      const created = res?.data ?? res;
      const testCaseId = created?.id;

      let hasSteps = steps.some((s) => s.action || s.expected);
      let hasActors = actors.some((a) => a.actor_name);
      if (testCaseId && (hasSteps || hasActors)) {
        await submitParts(testCaseId, hasSteps, hasActors);
      }
      goto(testCaseId ? `/tests/${testCaseId}` : "/tests");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al guardar";
    } finally {
      submitting = false;
    }
  }

  async function submitParts(testCaseId, hasSteps, hasActors) {
    const { http } = await import("$lib/api/http.js");
    if (hasSteps) {
      for (const s of steps) {
        if (s.action || s.expected) {
          await http.post(`/test-cases/${testCaseId}/steps`, {
            action: s.action,
            expected: s.expected,
            type: s.type,
          });
        }
      }
    }
    if (hasActors) {
      for (const a of actors) {
        if (a.actor_name) {
          await http.post(`/test-cases/${testCaseId}/actors`, { actor_name: a.actor_name });
        }
      }
    }
  }
</script>

<div class="max-w-4xl mx-auto p-6 space-y-6">
  <div class="flex items-center gap-4">
    <a
      href={selectedComponentId ? `/components/${selectedComponentId}` : "/tests"}
      class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
    >
      <ArrowLeft size={20} />
    </a>
    <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Crear Test Case</h1>
  </div>

  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else}
    <form
      onsubmit={(e) => {
        e.preventDefault();
        handleSubmit();
      }}
      class="space-y-8"
    >
      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6">
        <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark border-b border-border-light dark:border-border-dark pb-2">
          Información básica
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label for="componentId" class="block text-sm font-medium">Componente</label>
            <select id="componentId" bind:value={selectedComponentId} required class="form-input">
              <option value="" disabled>Seleccioná un componente</option>
              {#each components as component}
                <option value={component.id}>{component.name}</option>
              {/each}
            </select>
          </div>
          <div class="space-y-2">
            <label for="title" class="block text-sm font-medium">Título del test case</label>
            <input type="text" id="title" required placeholder="ej. Login con credenciales válidas" bind:value={form.title} class="form-input" />
          </div>
        </div>

        <div class="space-y-2">
          <label for="description" class="block text-sm font-medium">Descripción</label>
          <textarea id="description" rows="2" bind:value={form.description} class="form-input resize-none"></textarea>
        </div>

        <div class="space-y-2">
          <label for="status" class="block text-sm font-medium">Estado de ejecución</label>
          <select id="status" bind:value={form.status} class="form-input">
            <option value="untested">Sin probar</option>
            <option value="passed">Aprobado</option>
            <option value="failed">Fallido</option>
            <option value="blocked">Bloqueado</option>
          </select>
        </div>
      </div>

      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6">
        <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark border-b border-border-light dark:border-border-dark pb-2">
          Condiciones y resultados
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label for="preconditions" class="block text-sm font-medium">Precondiciones</label>
            <textarea id="preconditions" rows="3" bind:value={form.preconditions} class="form-input resize-none"></textarea>
          </div>
          <div class="space-y-2">
            <label for="postconditions" class="block text-sm font-medium">Postcondiciones</label>
            <textarea id="postconditions" rows="3" bind:value={form.postconditions} class="form-input resize-none"></textarea>
          </div>
        </div>

        <div class="space-y-2">
          <label for="expected_result" class="block text-sm font-medium">Resultado esperado global</label>
          <textarea id="expected_result" rows="2" bind:value={form.expected_result} class="form-input resize-none"></textarea>
        </div>
      </div>

      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6">
        <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark pb-2">
          <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark">Actores</h2>
          <button
            type="button"
            onclick={addActor}
            class="text-sm text-indigo-500 hover:text-indigo-400 font-medium flex items-center gap-1"
          >
            <Plus size={16} /> Agregar actor
          </button>
        </div>
        <div class="space-y-3">
          {#each actors as actor, i}
            <div class="flex gap-3">
              <input type="text" bind:value={actor.actor_name} placeholder="Nombre del actor (ej. Admin)" class="form-input flex-1" />
              <button
                type="button"
                onclick={() => removeActor(i)}
                class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors"
              >
                <Trash2 size={20} />
              </button>
            </div>
          {/each}
        </div>
      </div>

      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6">
        <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark pb-2">
          <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark">Pasos del test</h2>
          <button
            type="button"
            onclick={addStep}
            class="text-sm text-indigo-500 hover:text-indigo-400 font-medium flex items-center gap-1"
          >
            <Plus size={16} /> Agregar paso
          </button>
        </div>
        <div class="space-y-4">
          {#each steps as step, i}
            <div class="flex gap-4 items-start p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-border-light dark:border-border-dark">
              <div class="pt-3 font-mono text-sm text-text-sec-light dark:text-text-sec-dark w-6">{step.step_number}.</div>
              <div class="flex-1 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div class="md:col-span-2">
                    <input type="text" bind:value={step.action} placeholder="Acción" class="form-input text-sm" />
                  </div>
                  <div>
                    <select bind:value={step.type} class="form-input text-sm">
                      <option value="normal">Normal</option>
                      <option value="alternativo">Alternativo</option>
                      <option value="excepcion">Excepción</option>
                    </select>
                  </div>
                </div>
                <input type="text" bind:value={step.expected} placeholder="Resultado esperado" class="form-input text-sm" />
              </div>
              <button
                type="button"
                onclick={() => removeStep(i)}
                class="mt-2 p-2 text-text-sec-light dark:text-text-sec-dark hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors"
              >
                <Trash2 size={18} />
              </button>
            </div>
          {/each}
        </div>
      </div>

      {#if error}
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
      {/if}

      <div class="flex justify-end pt-4 pb-12">
        <button
          type="submit"
          disabled={submitting}
          class="flex items-center gap-2 px-8 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl font-medium transition-colors"
        >
          <Save size={20} />
          <span>{submitting ? "Guardando..." : "Guardar Test Case"}</span>
        </button>
      </div>
    </form>
  {/if}
</div>
