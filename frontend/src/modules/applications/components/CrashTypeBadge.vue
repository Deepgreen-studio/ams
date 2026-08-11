<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
    :class="classes"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ displayLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: { type: String, default: 'crash' },
  label: { type: String, default: '' },
});

const displayLabel = computed(() => {
  if (props.label) return props.label;
  return String(props.type || 'crash')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
});

const classes = computed(() => {
  switch (props.type) {
    case 'anr':
      return 'border-sky-500 text-sky-700';
    case 'api_error':
      return 'border-amber-500 text-amber-700';
    case 'crash':
    default:
      return 'border-rose-500 text-rose-700';
  }
});

const dotClass = computed(() => {
  switch (props.type) {
    case 'anr':
      return 'bg-sky-500';
    case 'api_error':
      return 'bg-amber-500';
    case 'crash':
    default:
      return 'bg-rose-500';
  }
});
</script>
