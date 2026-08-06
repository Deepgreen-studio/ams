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
    review: 'bg-amber-50 text-amber-700',
    approved: 'bg-emerald-50 text-emerald-700',
    published: 'bg-sky-50 text-sky-700',
    archived: 'bg-slate-100 text-slate-500',
    pending: 'bg-amber-50 text-amber-700',
    rejected: 'bg-rose-50 text-rose-700',
  };
  return map[props.status] || 'bg-slate-100 text-slate-700';
});
</script>
