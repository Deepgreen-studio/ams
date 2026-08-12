<template>
  <span class="group relative inline-flex">
    <slot />
    <span
      role="tooltip"
      class="pointer-events-none absolute left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-[8px] bg-slate-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition duration-150 group-hover:opacity-100 group-focus-within:opacity-100"
      :class="placementClass"
    >
      {{ text }}
      <span
        class="absolute left-1/2 h-1.5 w-1.5 -translate-x-1/2 rotate-45 bg-slate-900"
        :class="arrowClass"
        aria-hidden="true"
      />
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  text: {
    type: String,
    required: true,
  },
  placement: {
    type: String,
    default: 'top',
    validator: (value) => ['top', 'bottom'].includes(value),
  },
});

const placementClass = computed(() =>
  props.placement === 'bottom' ? 'top-full mt-2' : 'bottom-full mb-2',
);

const arrowClass = computed(() =>
  props.placement === 'bottom' ? '-top-0.5' : '-bottom-0.5',
);
</script>
