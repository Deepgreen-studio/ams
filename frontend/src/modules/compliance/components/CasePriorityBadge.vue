<template>
  <span
    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
    :class="classes"
  >
    {{ label || priority }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  priority: { type: String, default: '' },
  label: { type: String, default: '' },
});

const classes = computed(() => {
  switch (props.priority) {
    case 'low':
      return 'bg-slate-100 text-slate-600';
    case 'medium':
      return 'bg-sky-50 text-sky-700';
    case 'high':
      return 'bg-amber-50 text-amber-700';
    case 'critical':
      return 'bg-rose-50 text-rose-700';
    default:
      return 'bg-slate-100 text-slate-600';
  }
});
</script>
