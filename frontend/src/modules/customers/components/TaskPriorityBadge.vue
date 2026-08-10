<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
    :class="classes"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  priority: {
    type: String,
    default: 'medium',
  },
});

const label = computed(() => {
  const value = props.priority || 'medium';
  return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
});

const classes = computed(() => {
  switch (props.priority) {
    case 'urgent':
      return 'border-rose-500 text-rose-700';
    case 'high':
      return 'border-orange-500 text-orange-700';
    case 'low':
      return 'border-slate-400 text-slate-600';
    case 'medium':
    default:
      return 'border-amber-500 text-amber-700';
  }
});

const dotClass = computed(() => {
  switch (props.priority) {
    case 'urgent':
      return 'bg-rose-500';
    case 'high':
      return 'bg-orange-500';
    case 'low':
      return 'bg-slate-400';
    case 'medium':
    default:
      return 'bg-amber-500';
  }
});
</script>
