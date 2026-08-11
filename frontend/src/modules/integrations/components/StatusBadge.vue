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
  status: { type: String, default: 'draft' },
  kind: { type: String, default: 'status' },
});

const label = computed(() =>
  (props.status || 'draft').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
);

const classes = computed(() => {
  if (props.kind === 'health') {
    switch (props.status) {
      case 'healthy':
        return 'border-emerald-600 text-emerald-700';
      case 'degraded':
        return 'border-amber-500 text-amber-700';
      case 'unhealthy':
        return 'border-rose-500 text-rose-700';
      default:
        return 'border-slate-400 text-slate-600';
    }
  }

  switch (props.status) {
    case 'active':
      return 'border-emerald-600 text-emerald-700';
    case 'inactive':
      return 'border-slate-400 text-slate-600';
    case 'error':
      return 'border-rose-500 text-rose-700';
    case 'draft':
      return 'border-amber-500 text-amber-700';
    default:
      return 'border-slate-400 text-slate-600';
  }
});

const dotClass = computed(() => {
  if (props.kind === 'health') {
    switch (props.status) {
      case 'healthy':
        return 'bg-emerald-600';
      case 'degraded':
        return 'bg-amber-500';
      case 'unhealthy':
        return 'bg-rose-500';
      default:
        return 'bg-slate-400';
    }
  }

  switch (props.status) {
    case 'active':
      return 'bg-emerald-600';
    case 'inactive':
      return 'bg-slate-400';
    case 'error':
      return 'bg-rose-500';
    case 'draft':
      return 'bg-amber-500';
    default:
      return 'bg-slate-400';
  }
});
</script>
