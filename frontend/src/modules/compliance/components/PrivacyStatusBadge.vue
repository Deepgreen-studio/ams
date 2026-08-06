<template>
  <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium" :class="classes">
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
    case 'submitted':
      return 'bg-sky-50 text-sky-700';
    case 'identity_pending':
      return 'bg-amber-50 text-amber-700';
    case 'under_review':
      return 'bg-indigo-50 text-indigo-700';
    case 'approved':
      return 'bg-emerald-50 text-emerald-700';
    case 'rejected':
      return 'bg-rose-50 text-rose-700';
    case 'in_progress':
      return 'bg-violet-50 text-violet-700';
    case 'completed':
      return 'bg-slate-100 text-slate-700';
    case 'cancelled':
      return 'bg-rose-50 text-rose-700';
    default:
      return 'bg-slate-100 text-slate-600';
  }
});
</script>
