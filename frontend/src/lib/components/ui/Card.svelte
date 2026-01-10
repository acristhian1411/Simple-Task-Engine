<script>
  /**
   * Card component with variants and interactive states
   * @component
   */

  /** @type {'default' | 'board' | 'task'} */
  export let variant = 'default';
  
  /** @type {boolean} */
  export let hover = true;
  
  /** @type {boolean} */
  export let clickable = false;
  
  /** @type {() => void} */
  export let onClick = null;

  // Compute classes based on props
  $: variantClass = {
    default: 'bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg shadow-sm',
    board: 'board-card',
    task: 'task-card'
  }[variant];

  $: hoverClass = hover ? 'hover:shadow-lg transition-shadow duration-200' : '';
  $: clickableClass = clickable || onClick ? 'cursor-pointer' : '';

  function handleClick(event) {
    if (onClick) {
      onClick(event);
    }
  }

  function handleKeydown(event) {
    if ((clickable || onClick) && (event.key === 'Enter' || event.key === ' ')) {
      event.preventDefault();
      handleClick(event);
    }
  }
</script>

{#if clickable || onClick}
<div
  class="{variantClass} {hoverClass} {clickableClass}"
  role="button"
  tabindex="0"
  on:click={handleClick}
  on:keydown={handleKeydown}
  {...$$restProps}
>
  <slot />
</div>
{:else}
<div
  class="{variantClass} {hoverClass} {clickableClass}"
  {...$$restProps}
>
  <slot />
</div>
{/if}