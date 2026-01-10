<script>
  /**
   * Badge component for status indicators
   * @component
   */

  /** @type {'todo' | 'progress' | 'blocked' | 'done' | 'default'} */
  export let variant = 'default';
  
  /** @type {'sm' | 'md'} */
  export let size = 'md';

  // Compute classes based on props
  $: variantClass = {
    todo: 'status-todo',
    progress: 'status-progress',
    blocked: 'status-blocked', 
    done: 'status-done',
    default: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
  }[variant];

  $: sizeClass = {
    sm: 'px-1.5 py-0.5 text-xs',
    md: 'px-2 py-0.5 text-xs'
  }[size];
</script>

<span
  class="status-badge {variantClass} {sizeClass}"
  {...$$restProps}
>
  <slot />
</span>