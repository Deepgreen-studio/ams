<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="tone"
  >
    {{ label || severity || '—' }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  severity: { type: String, default: '' },
  label: { type: String, default: '' },
});

const tone = computed(() => {
  const map = {
    low: 'bg-emerald-50 text-emerald-700',
    medium: 'bg-amber-50 text-amber-700',
    high: 'bg-orange-50 text-orange-700',
    critical: 'bg-rose-50 text-rose-700',
  };
  return map[props.severity] || 'bg-slate-100 text-slate-700';
});
</script>
