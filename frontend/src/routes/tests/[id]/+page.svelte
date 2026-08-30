<script>
  import { onMount } from "svelte";
  import { goto } from "$app/navigation";
  import { ArrowLeft, Save, Plus, Trash2, History } from "lucide-svelte";
  import { refreshMe } from "$lib/stores/auth.js";
  import { openAuditSidebar } from "$lib/stores/auditSidebar.svelte.js";
  import { getTestCase, updateTestCase, deleteTestCase, getTestSteps, getActors } from "$lib/api/test-cases.js";
  import { getComponents } from "$lib/api/components.js";
  import Comments from "$lib/components/Comments.svelte";

  let loading = $state(true);
  let error = $state("");
  let submitting = $state(false);

  let testCase = $state(null);
  let components = $state([]);
  let steps = $state([]);
  let actors = $state([]);
  let selectedComponentId = $state("");

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

  async function load() {
    loading = true;
    error = "";
    try {
      const me = await refreshMe();
      if (!me) return goto("/login");
      const id = window.location.pathname.split("/").filter(Boolean).pop();
      const [tcRes, compRes, stepsRes, actorsRes] = await Promise.all([
        getTestCase(id),
        getComponents({ per_page: 100 }),
        getTestSteps(id),
        getActors(id),
      ]);
      testCase = tcRes?.data ?? tcRes;
      components = compRes?.data ?? compRes ?? [];
      steps = stepsRes?.data ?? stepsRes ?? [];
      actors = actorsRes?.data ?? actorsRes ?? [];
      selectedComponentId = testCase?.component_id ?? "";
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error cargando test case";
    } finally {
      loading = false;
    }
  }

  async function handleSubmit() {
    if (!testCase.title) {
      error = "El título es obligatorio";
      return;
    }
    submitting = true;
    error = "";
    try {
      await updateTestCase(testCase.id, {
        title: testCase.title,
        description: testCase.description || null,
        component_id: selectedComponentId || null,
        status: testCase.status,
        preconditions: testCase.preconditions || null,
        postconditions: testCase.postconditions || null,
        expected_result: testCase.expected_result || null,
      });
      const { http } = await import("$lib/api/http.js");
      for (const [i, s] of steps.entries()) {
        if (s.id) {
          await http.put(`/test-steps/${s.id}`, {
            action: s.action,
            expected: s.expected,
            type: s.type,
          });
        } else if (s.action || s.expected) {
          await http.post(`/test-cases/${testCase.id}/steps`, {
            action: s.action,
            expected: s.expected,
            type: s.type,
          });
        }
      }
      for (const a of actors) {
        if (a.id) {
          await http.put(`/test-case-actors/${a.id}`, { actor_name: a.actor_name });
        } else if (a.actor_name) {
          await http.post(`/test-cases/${testCase.id}/actors`, { actor_name: a.actor_name });
        }
      }
      goto("/tests");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al guardar";
    } finally {
      submitting = false;
    }
  }

  async function handleDelete() {
    if (!confirm(`¿Eliminar el test case "${testCase.title}"?`)) return;
    try {
      await deleteTestCase(testCase.id);
      goto("/tests");
    } catch (e) {
      error = e?.response?.data?.error ?? e?.message ?? "Error al eliminar";
    }
  }

  onMount(load);
</script>

<div class="max-w-4xl mx-auto p-6 space-y-6">
  {#if loading}
    <p class="text-sm text-text-sec-light dark:text-text-sec-dark">Cargando...</p>
  {:else if error && !testCase}
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">{error}</div>
  {:else if testCase}
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <a
          href={selectedComponentId ? `/components/${selectedComponentId}` : "/tests"}
          class="p-2 text-text-sec-light dark:text-text-sec-dark hover:text-text-main-light dark:hover:text-text-main-dark hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
        >
          <ArrowLeft size={20} />
        </a>
        <h1 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark">Editar Test Case</h1>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          onclick={() => openAuditSidebar(testCase.id, "TestCases")}
          class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-text-main-light dark:text-text-main-dark rounded-lg font-medium transition-colors border border-border-light dark:border-border-dark"
        >
          <History size={18} />
          <span>Historial</span>
        </button>
        <button
          type="button"
          onclick={handleDelete}
          class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 rounded-lg font-medium transition-colors border border-red-500/20"
        >
          <Trash2 size={18} />
          <span>Eliminar</span>
        </button>
      </div>
    </div>
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
            <input type="text" id="title" bind:value={testCase.title} required class="form-input" />
          </div>
        </div>

        <div class="space-y-2">
          <label for="description" class="block text-sm font-medium">Descripción</label>
          <textarea id="description" rows="2" bind:value={testCase.description} class="form-input resize-none"></textarea>
        </div>

        <div class="space-y-2">
          <label for="status" class="block text-sm font-medium">Estado de ejecución</label>
          <select id="status" bind:value={testCase.status} class="form-input">
            <option value="untested">Sin probar</option>
            <option value="passed">Aprobado</option>
            <option value="failed">Fallido</option>
            <option value="blocked">Bloqueado</option>
          </select>
        </div>

        {#if testCase.status === "failed"}
          <div class="mt-4 p-4 bg-red-500/5 border border-red-500/20 rounded-xl flex flex-wrap items-center justify-between gap-3">
            <span class="text-sm text-red-400">Este test case falló. ¿Querés reportar un bug?</span>
            <a
              href={`/bugs/new?testCaseId=${testCase.id}&componentId=${selectedComponentId}`}
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
              >Reportar Bug</a
            >
          </div>
        {/if}
      </div>

      <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 space-y-6">
        <h2 class="text-lg font-semibold text-text-main-light dark:text-text-main-dark border-b border-border-light dark:border-border-dark pb-2">
          Condiciones y resultados
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label for="preconditions" class="block text-sm font-medium">Precondiciones</label>
            <textarea id="preconditions" rows="3" bind:value={testCase.preconditions} class="form-input resize-none"></textarea>
          </div>
          <div class="space-y-2">
            <label for="postconditions" class="block text-sm font-medium">Postcondiciones</label>
            <textarea id="postconditions" rows="3" bind:value={testCase.postconditions} class="form-input resize-none"></textarea>
          </div>
        </div>

        <div class="space-y-2">
          <label for="expected_result" class="block text-sm font-medium">Resultado esperado global</label>
          <textarea id="expected_result" rows="2" bind:value={testCase.expected_result} class="form-input resize-none"></textarea>
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
          <span>{submitting ? "Guardando..." : "Guardar cambios"}</span>
        </button>
      </div>
    </form>
    <Comments refId={testCase.id} refTable="test-cases" />
  {/if}
</div>
