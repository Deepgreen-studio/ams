<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
    :class="classes"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  priority: { type: String, default: 'medium' },
  label: { type: String, default: '' },
});

const label = computed(
  () =>
    props.label ||
    (props.priority || 'medium').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);

const classes = computed(() => {
  switch (props.priority) {
    case 'low':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    case 'medium':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'high':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'critical':
      return 'bg-orange-50 text-orange-800 ring-orange-600/20';
    case 'emergency':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});

const dotClass = computed(() => {
  switch (props.priority) {
    case 'low':
      return 'bg-slate-400';
    case 'medium':
      return 'bg-sky-500';
    case 'high':
      return 'bg-amber-500';
    case 'critical':
      return 'bg-orange-500';
    case 'emergency':
      return 'bg-rose-600 animate-pulse';
    default:
      return 'bg-slate-400';
  }
});
</script>
