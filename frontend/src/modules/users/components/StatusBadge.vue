<template>
  <span
    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
    :class="classes"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    default: 'active',
  },
});

const label = computed(() => {
  const value = props.status || 'active';
  return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
});

const classes = computed(() => {
  switch (props.status) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'inactive':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    case 'suspended':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'pending':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});
</script>
