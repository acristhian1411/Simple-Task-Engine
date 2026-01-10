<script>
  /**
   * Avatar component with different sizes and group support
   * @component
   */

  /** @type {string} */
  export let src = '';
  
  /** @type {string} */
  export let alt = '';
  
  /** @type {'sm' | 'md' | 'lg'} */
  export let size = 'md';
  
  /** @type {string} */
  export let name = '';
  
  /** @type {boolean} */
  export let group = false;

  // Compute classes based on props
  $: sizeClass = {
    sm: 'avatar-sm',
    md: 'avatar-md', 
    lg: 'avatar-lg'
  }[size];

  // Generate initials from name if no src provided
  $: initials = name
    ? name.split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2)
    : '';

  // Generate background color based on name
  $: backgroundColor = name 
    ? `hsl(${name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % 360}, 50%, 50%)`
    : '#6b7280';
</script>

{#if src}
  <img
    {src}
    {alt}
    class="avatar {sizeClass} {group ? 'border-2 border-white dark:border-gray-800' : ''}"
    {...$$restProps}
  />
{:else}
  <div
    class="avatar {sizeClass} {group ? 'border-2 border-white dark:border-gray-800' : ''} flex items-center justify-center text-white font-medium text-xs"
    style="background-color: {backgroundColor}"
    title={name || alt}
    {...$$restProps}
  >
    {initials || '?'}
  </div>
{/if}