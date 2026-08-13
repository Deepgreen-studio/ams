<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
    :class="tone"
  >
    {{ label || status }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'not_applicable' },
  label: { type: String, default: '' },
});

const tone = computed(() => {
  switch (props.status) {
    case 'on_track':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'at_risk':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'breached':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'paused':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'met':
      return 'bg-brand-50 text-brand-700 ring-brand-500/20';
    default:
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
  }
});
</script>
