<template>
  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'open' },
  label: { type: String, default: '' },
});

const label = computed(
  () =>
    props.label ||
    (props.status || 'open').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);

const classes = computed(() => {
  switch (props.status) {
    case 'open':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'pending':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'in_progress':
      return 'bg-indigo-50 text-indigo-700 ring-indigo-600/20';
    case 'waiting_for_customer':
      return 'bg-violet-50 text-violet-700 ring-violet-600/20';
    case 'resolved':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'closed':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    case 'reopened':
      return 'bg-orange-50 text-orange-800 ring-orange-600/20';
    case 'cancelled':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});
</script>
