<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'unknown' },
  kind: { type: String, default: 'health' },
});

const label = computed(() => (props.status || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()));

const classes = computed(() => {
  if (props.kind === 'status') {
    switch (props.status) {
      case 'active': return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
      case 'maintenance': return 'bg-amber-50 text-amber-800 ring-amber-600/20';
      default: return 'bg-slate-50 text-slate-600 ring-slate-500/20';
    }
  }

  switch (props.status) {
    case 'healthy': return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'degraded': return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'unhealthy': return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default: return 'bg-slate-50 text-slate-600 ring-slate-500/20';
  }
});
</script>
