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
    case 'trialing':
      return 'border-sky-500 text-sky-700';
    case 'past_due':
      return 'border-amber-500 text-amber-700';
    case 'suspended':
      return 'border-orange-500 text-orange-700';
    case 'cancelled':
      return 'border-rose-500 text-rose-700';
    case 'expired':
      return 'border-slate-400 text-slate-600';
    default:
      return 'border-slate-400 text-slate-600';
  }
});

const dotClass = computed(() => {
  switch (props.status) {
    case 'active':
      return 'bg-emerald-600';
    case 'trialing':
      return 'bg-sky-500';
    case 'past_due':
      return 'bg-amber-500';
    case 'suspended':
      return 'bg-orange-500';
    case 'cancelled':
      return 'bg-rose-500';
    case 'expired':
      return 'bg-slate-400';
    default:
      return 'bg-slate-400';
  }
});
</script>
