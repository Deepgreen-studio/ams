<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
    :class="classes"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    default: 'open',
  },
});

const label = computed(() => {
  const value = props.status || 'open';
  return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
});

const classes = computed(() => {
  switch (props.status) {
    case 'completed':
      return 'border-emerald-600 text-emerald-700';
    case 'in_progress':
      return 'border-brand-500 text-brand-700';
    case 'cancelled':
      return 'border-slate-400 text-slate-600';
    case 'open':
    default:
      return 'border-amber-500 text-amber-700';
  }
});

const dotClass = computed(() => {
  switch (props.status) {
    case 'completed':
      return 'bg-emerald-600';
    case 'in_progress':
      return 'bg-brand-500';
    case 'cancelled':
      return 'bg-slate-400';
    case 'open':
    default:
      return 'bg-amber-500';
  }
});
</script>
