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
  status: { type: String, default: 'active' },
});

const label = computed(() =>
  (props.status || 'active').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
);

const classes = computed(() => {
  switch (props.status) {
    case 'active':
      return 'border-emerald-600 text-emerald-700';
    case 'draft':
      return 'border-slate-400 text-slate-600';
    case 'expired':
      return 'border-amber-500 text-amber-700';
    case 'archived':
      return 'border-rose-500 text-rose-700';
    default:
      return 'border-slate-400 text-slate-600';
  }
});

const dotClass = computed(() => {
  switch (props.status) {
    case 'active':
      return 'bg-emerald-600';
    case 'draft':
      return 'bg-slate-400';
    case 'expired':
      return 'bg-amber-500';
    case 'archived':
      return 'bg-rose-500';
    default:
      return 'bg-slate-400';
  }
});
</script>
