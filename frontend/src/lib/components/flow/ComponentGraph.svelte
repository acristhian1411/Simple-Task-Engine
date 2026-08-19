<script>
  import { onMount } from 'svelte';
  import { SvelteFlow, Background, Controls, MiniMap } from '@xyflow/svelte';
  import '@xyflow/svelte/dist/style.css';
  import { getComponentDependents } from '$lib/api/components.js';

  let { component } = $props();

  let nodes = $state([]);
  let edges = $state([]);

  onMount(async () => {
    try {
      const data = await getComponentDependents(component.id);
      const rows = Array.isArray(data) ? data : data?.data ?? [];
      const centerNode = {
        id: `c${component.id}`,
        position: { x: 300, y: 60 },
        data: { label: component.name },
        style:
          'background:#0f6cbd;color:#fff;font-weight:700;border-radius:8px;padding:6px 12px;min-width:120px;text-align:center',
      };

      if (!rows || rows.length === 0) {
        nodes = [centerNode];
        edges = [];
        return;
      }

      const dependentNodes = rows.map((dep, idx) => ({
        id: `c${dep.id}`,
        position: { x: 80 + idx * 200, y: 230 },
        data: { label: dep.name },
        style: 'padding:4px 10px;border-radius:6px;min-width:100px;text-align:center;font-size:12px',
      }));

      const nextEdges = rows.map((dep) => ({
        id: `e${dep.id}-${component.id}`,
        source: `c${dep.id}`,
        target: `c${component.id}`,
        type: 'smoothstep',
        style: { stroke: dep.criticality === 'critical' ? '#c4314b' : '#697586' },
        animated: dep.criticality === 'critical',
      }));

      nodes = [centerNode, ...dependentNodes];
      edges = nextEdges;
    } catch (e) {
      nodes = [
        {
          id: `c${component.id}`,
          position: { x: 300, y: 60 },
          data: { label: component.name },
        },
      ];
      edges = [];
    }
  });
</script>

<div class="w-full h-[400px]">
  <SvelteFlow
    {nodes}
    {edges}
    fitView
    nodesDraggable
    nodesConnectable={false}
    proOptions={{ hideAttribution: true }}
  >
    <Background />
    <MiniMap />
    <Controls />
  </SvelteFlow>
</div>
