<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'draft' },
});

const label = computed(() => (props.status || 'draft').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()));

const classes = computed(() => {
  switch (props.status) {
    case 'production': return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'beta': return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'testing': return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'deprecated': return 'bg-slate-50 text-slate-600 ring-slate-500/20';
    case 'rollback': return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'draft':
    default: return 'bg-violet-50 text-violet-700 ring-violet-600/20';
  }
});
</script>
