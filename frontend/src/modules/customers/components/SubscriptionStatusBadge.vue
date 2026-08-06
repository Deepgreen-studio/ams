<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'active' },
});

const label = computed(() =>
  (props.status || 'active').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);

const classes = computed(() => {
  switch (props.status) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'trialing':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'past_due':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'suspended':
      return 'bg-orange-50 text-orange-700 ring-orange-600/20';
    case 'cancelled':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'expired':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});
</script>
