<script>
  /**
   * Button component with variants, sizes, and states
   * @component
   */

  /** @type {'primary' | 'secondary' | 'ghost'} */
  export let variant = 'primary';
  
  /** @type {'sm' | 'md' | 'lg'} */
  export let size = 'md';
  
  /** @type {boolean} */
  export let disabled = false;
  
  /** @type {boolean} */
  export let loading = false;
  
  /** @type {string} */
  export let type = 'button';
  
  /** @type {() => void} */
  export let onClick = null;

  // Compute classes based on props
  $: variantClass = {
    primary: 'btn-primary',
    secondary: 'btn-secondary', 
    ghost: 'btn-ghost'
  }[variant];

  $: sizeClass = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
  }[size];

  $: disabledClass = disabled || loading ? 'opacity-50 cursor-not-allowed' : '';

  function handleClick(event) {
    if (disabled || loading) {
      event.preventDefault();
      return;
    }
    if (onClick) {
      onClick(event);
    }
  }
</script>

<button
  {type}
  class="{variantClass} {sizeClass} {disabledClass} font-bold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
  {disabled}
  on:click={handleClick}
  {...$$restProps}
>
  {#if loading}
    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
  {/if}
  <slot />
</button>