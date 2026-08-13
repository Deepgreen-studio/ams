<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="tone"
  >
    {{ label || status || '—' }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: '' },
  label: { type: String, default: '' },
});

const tone = computed(() => {
  const map = {
    draft: 'bg-slate-100 text-slate-700',
    queued: 'bg-amber-50 text-amber-700',
    sent: 'bg-emerald-50 text-emerald-700',
    failed: 'bg-rose-50 text-rose-700',
    acknowledged: 'bg-sky-50 text-sky-700',
  };
  return map[props.status] || 'bg-slate-100 text-slate-700';
});
</script>
