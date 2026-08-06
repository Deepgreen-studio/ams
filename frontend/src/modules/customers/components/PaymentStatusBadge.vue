<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'pending' },
});

const label = computed(() =>
  (props.status || 'pending').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);

const classes = computed(() => {
  switch (props.status) {
    case 'paid':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'not_required':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    case 'pending':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'past_due':
    case 'failed':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'refunded':
      return 'bg-orange-50 text-orange-700 ring-orange-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});
</script>
