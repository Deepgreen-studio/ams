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
  status: { type: String, default: 'unknown' },
  kind: { type: String, default: 'health' },
});

const label = computed(() =>
  (props.status || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
);

const classes = computed(() => {
  if (props.kind === 'status') {
    switch (props.status) {
      case 'active':
        return 'border-emerald-600 text-emerald-700';
      case 'maintenance':
        return 'border-amber-500 text-amber-700';
      default:
        return 'border-slate-400 text-slate-600';
    }
  }

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
});

const dotClass = computed(() => {
  if (props.kind === 'status') {
    switch (props.status) {
      case 'active':
        return 'bg-emerald-600';
      case 'maintenance':
        return 'bg-amber-500';
      default:
        return 'bg-slate-400';
    }
  }

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
});
</script>
