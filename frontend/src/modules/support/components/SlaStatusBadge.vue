<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
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
      return 'bg-emerald-50 text-emerald-700';
    case 'at_risk':
      return 'bg-amber-50 text-amber-800';
    case 'breached':
      return 'bg-rose-50 text-rose-700';
    case 'paused':
      return 'bg-slate-100 text-slate-700';
    case 'met':
      return 'bg-sky-50 text-sky-700';
    default:
      return 'bg-slate-50 text-slate-500';
  }
});
</script>
