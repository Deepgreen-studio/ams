<template>
  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="classes">
    {{ label || status }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: '' },
  label: { type: String, default: '' },
});

const classes = computed(() => {
  switch (props.status) {
    case 'granted':
      return 'bg-emerald-50 text-emerald-700';
    case 'withdrawn':
      return 'bg-rose-50 text-rose-700';
    case 'pending':
      return 'bg-amber-50 text-amber-700';
    case 'expired':
      return 'bg-slate-100 text-slate-600';
    default:
      return 'bg-slate-100 text-slate-600';
  }
});
</script>
