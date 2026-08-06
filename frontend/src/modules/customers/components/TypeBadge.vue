<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: { type: String, default: 'individual' },
});

const label = computed(() =>
  (props.type || 'individual').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);

const classes = computed(() => {
  switch (props.type) {
    case 'individual':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'business':
      return 'bg-violet-50 text-violet-700 ring-violet-600/20';
    case 'enterprise':
      return 'bg-indigo-50 text-indigo-700 ring-indigo-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});
</script>
