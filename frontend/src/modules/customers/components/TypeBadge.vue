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
  type: {
    type: String,
    default: 'individual',
  },
});

const label = computed(() => {
  const value = props.type || 'individual';
  return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
});

const classes = computed(() => {
  switch (props.type) {
    case 'individual':
      return 'border-sky-500 text-sky-700';
    case 'business':
      return 'border-violet-500 text-violet-700';
    case 'enterprise':
      return 'border-indigo-500 text-indigo-700';
    default:
      return 'border-slate-400 text-slate-600';
  }
});

const dotClass = computed(() => {
  switch (props.type) {
    case 'individual':
      return 'bg-sky-500';
    case 'business':
      return 'bg-violet-500';
    case 'enterprise':
      return 'bg-indigo-500';
    default:
      return 'bg-slate-400';
  }
});
</script>
