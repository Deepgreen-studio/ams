<template>
  <span
    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
    :class="toneClass"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: '' },
  label: { type: String, default: '' },
});

const label = computed(() => props.label || props.status || '—');

const toneClass = computed(() => {
  switch ((props.status || '').toLowerCase()) {
    case 'published':
      return 'bg-emerald-50 text-emerald-700';
    case 'approved':
      return 'bg-teal-50 text-teal-700';
    case 'reviewed':
      return 'bg-violet-50 text-violet-700';
    case 'pending_review':
      return 'bg-amber-50 text-amber-800';
    case 'rejected':
      return 'bg-rose-50 text-rose-700';
    case 'scheduled':
      return 'bg-sky-50 text-sky-700';
    case 'archived':
      return 'bg-slate-100 text-slate-600';
    case 'draft':
    default:
      return 'bg-slate-50 text-slate-700';
  }
});
</script>
