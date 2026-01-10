<script>
  /**
   * ProgressBar component for task completion visualization
   * @component
   */

  /** @type {number} */
  export let value = 0;
  
  /** @type {number} */
  export let max = 100;
  
  /** @type {'primary' | 'success' | 'warning' | 'danger'} */
  export let color = 'primary';
  
  /** @type {boolean} */
  export let animated = false;
  
  /** @type {boolean} */
  export let showLabel = false;

  // Calculate percentage
  $: percentage = Math.min(Math.max((value / max) * 100, 0), 100);
  
  // Compute classes based on props
  $: colorClass = {
    primary: 'bg-primary',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    danger: 'bg-red-500'
  }[color];

  $: animatedClass = animated ? 'transition-all duration-300 ease-out' : '';
</script>

<div class="w-full">
  {#if showLabel}
    <div class="flex justify-between items-center mb-1">
      <slot name="label" />
      <span class="text-sm text-text-sec-light dark:text-text-sec-dark">
        {Math.round(percentage)}%
      </span>
    </div>
  {/if}
  
  <div class="progress-bar">
    <div
      class="progress-fill {colorClass} {animatedClass}"
      style="width: {percentage}%"
      role="progressbar"
      aria-valuenow={value}
      aria-valuemin="0"
      aria-valuemax={max}
    >
      {#if animated}
        <div class="absolute inset-0 bg-white/20 animate-pulse rounded-full"></div>
      {/if}
    </div>
  </div>
</div>