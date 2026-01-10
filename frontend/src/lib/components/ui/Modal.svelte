<script>
  /**
   * Modal component with different sizes and positions
   * @component
   */
  import { createEventDispatcher, onMount } from 'svelte';

  /** @type {boolean} */
  export let open = false;
  
  /** @type {'sm' | 'md' | 'lg' | 'xl'} */
  export let size = 'md';
  
  /** @type {'center' | 'right'} */
  export let position = 'center';
  
  /** @type {boolean} */
  export let closeOnBackdrop = true;
  
  /** @type {boolean} */
  export let closeOnEscape = true;

  const dispatch = createEventDispatcher();

  // Compute classes based on props
  $: sizeClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-2xl'
  }[size];

  $: positionClass = position === 'right' 
    ? 'modal-drawer' 
    : `fixed inset-0 flex items-center justify-center p-4 z-50`;

  $: contentClass = position === 'right'
    ? ''
    : `bg-surface-light dark:bg-surface-dark rounded-lg shadow-2xl ${sizeClass} w-full`;

  function handleBackdropClick(event) {
    if (closeOnBackdrop && event.target === event.currentTarget) {
      close();
    }
  }

  function handleKeydown(event) {
    if (closeOnEscape && event.key === 'Escape') {
      close();
    }
  }

  function close() {
    dispatch('close');
  }

  onMount(() => {
    if (closeOnEscape) {
      document.addEventListener('keydown', handleKeydown);
      return () => {
        document.removeEventListener('keydown', handleKeydown);
      };
    }
  });

  // Prevent body scroll when modal is open
  $: if (typeof document !== 'undefined') {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }
</script>

{#if open}
  <!-- Backdrop -->
  <div 
    class="modal-backdrop"
    on:click={handleBackdropClick}
    on:keydown={handleKeydown}
    role="dialog"
    aria-modal="true"
    tabindex="-1"
  >
    <!-- Modal Content -->
    <div class={positionClass}>
      <div class={contentClass}>
        <slot {close} />
      </div>
    </div>
  </div>
{/if}