<script>
  import { SvelteFlow, Background, Controls, MiniMap } from '@xyflow/svelte';
  import '@xyflow/svelte/dist/style.css';

  let { rootId, rootName, rows = [], mode = 'impact' } = $props();

  const CRITICAL_COLOR = '#c4314b';
  const OPTIONAL_COLOR = '#697586';
  const CENTER = { x: 420, y: 280 };
  const IMPACT_ROOT = { x: 520, y: 80 };

  const NODE_STYLE = 'padding:4px 10px;border-radius:6px;min-width:100px;text-align:center;font-size:12px';
  const ROOT_STYLE =
    'background:#0f6cbd;color:#fff;font-weight:700;border-radius:8px;padding:6px 12px;min-width:120px;text-align:center';

  function buildGraph() {
    const rootPos = mode === 'impact' ? IMPACT_ROOT : CENTER;
    const rootNode = {
      id: `n${rootId}`,
      position: rootPos,
      data: { label: rootName },
      style: ROOT_STYLE,
    };

    let otherNodes = [];
    if (mode === 'impact') {
      const byDepth = {};
      for (const row of rows) {
        const d = row.depth ?? 1;
        (byDepth[d] = byDepth[d] || []).push(row);
      }
      for (const [depthStr, group] of Object.entries(byDepth).sort(
        (a, b) => Number(a[0]) - Number(b[0]),
      )) {
        const depth = Number(depthStr);
        const spacing = 220;
        const startX = rootPos.x - ((group.length - 1) * spacing) / 2;
        group.forEach((row, i) => {
          otherNodes.push({
            id: `n${row.id}`,
            position: { x: startX + i * spacing, y: rootPos.y + depth * 145 },
            data: { label: row.name },
            style: NODE_STYLE,
          });
        });
      }
    } else {
      const radius = Math.max(180, rows.length * 45);
      otherNodes = rows.map((row, i) => {
        const angle = (2 * Math.PI * i) / rows.length - Math.PI / 2;
        return {
          id: `n${row.id}`,
          position: {
            x: CENTER.x + radius * Math.cos(angle),
            y: CENTER.y + radius * Math.sin(angle),
          },
          data: { label: row.name },
          style: NODE_STYLE,
        };
      });
    }

    const edges = rows
      .map((row) => {
        const isCritical = row.criticality === 'critical';
        const color = isCritical ? CRITICAL_COLOR : OPTIONAL_COLOR;
        let source;
        let target;
        if (mode === 'impact') {
          source = `n${row.tree_parent_id ?? rootId}`;
          target = `n${row.id}`;
        } else if (mode === 'dependencies') {
          source = `n${rootId}`;
          target = `n${row.id}`;
        } else {
          source = `n${row.id}`;
          target = `n${rootId}`;
        }
        if (source === target) return null;
        return {
          id: `e${source}-${target}`,
          source,
          target,
          type: 'smoothstep',
          style: { stroke: color, strokeWidth: isCritical ? 2 : 1.5 },
          animated: isCritical,
        };
      })
      .filter(Boolean);

    return { nodes: [rootNode, ...otherNodes], edges };
  }

  let nodes = $state([]);
  let edges = $state([]);

  $effect(() => {
    const built = buildGraph();
    nodes = built.nodes;
    edges = built.edges;
  });
</script>

<div class="w-full h-[520px]">
  <SvelteFlow
    {nodes}
    {edges}
    fitView
    fitViewOptions={{ padding: 0.25 }}
    nodesDraggable
    nodesConnectable={false}
    proOptions={{ hideAttribution: true }}
  >
    <Background />
    <Controls />
    <MiniMap />
  </SvelteFlow>
</div>
