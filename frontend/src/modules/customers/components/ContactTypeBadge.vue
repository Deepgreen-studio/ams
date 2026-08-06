<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: { type: String, default: 'support' },
});

const label = computed(() =>
  (props.type || 'support').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);

const classes = computed(() => {
  switch (props.type) {
    case 'primary':
      return 'bg-brand-50 text-brand-700 ring-brand-600/20';
    case 'technical':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'billing':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'support':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'emergency':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});
</script>
