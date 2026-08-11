<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
    :class="classes"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ displayLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'planned' },
  label: { type: String, default: '' },
});

const displayLabel = computed(() => {
  if (props.label) return props.label;
  return String(props.status || 'planned')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
});

const classes = computed(() => {
  switch (props.status) {
    case 'deployed':
      return 'border-emerald-600 text-emerald-700';
    case 'approved':
      return 'border-sky-500 text-sky-700';
    case 'pending_approval':
    case 'scheduled':
      return 'border-amber-500 text-amber-700';
    case 'failed':
    case 'rolled_back':
    case 'rejected':
      return 'border-rose-500 text-rose-700';
    case 'cancelled':
      return 'border-slate-400 text-slate-600';
    case 'planned':
    default:
      return 'border-violet-500 text-violet-700';
  }
});

const dotClass = computed(() => {
  switch (props.status) {
    case 'deployed':
      return 'bg-emerald-600';
    case 'approved':
      return 'bg-sky-500';
    case 'pending_approval':
    case 'scheduled':
      return 'bg-amber-500';
    case 'failed':
    case 'rolled_back':
    case 'rejected':
      return 'bg-rose-500';
    case 'cancelled':
      return 'bg-slate-400';
    case 'planned':
    default:
      return 'bg-violet-500';
  }
});
</script>
