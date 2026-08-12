<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium capitalize"
    :class="classes"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  direction: {
    type: String,
    default: 'outgoing',
  },
});

const label = computed(() => props.direction || 'outgoing');

const classes = computed(() => {
  switch (props.direction) {
    case 'incoming':
      return 'border-sky-600 text-sky-700';
    case 'outgoing':
    default:
      return 'border-slate-400 text-slate-600';
  }
});

const dotClass = computed(() => {
  switch (props.direction) {
    case 'incoming':
      return 'bg-sky-600';
    case 'outgoing':
    default:
      return 'bg-slate-400';
  }
});
</script>
