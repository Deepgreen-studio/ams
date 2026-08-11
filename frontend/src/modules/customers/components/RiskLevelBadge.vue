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
  level: {
    type: String,
    default: 'low',
  },
  /** When true, appends " Risk" to the label (e.g. "Low Risk"). */
  withSuffix: {
    type: Boolean,
    default: false,
  },
});

const normalized = computed(() => String(props.level || 'low').toLowerCase());

const label = computed(() => {
  const base = normalized.value.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  return props.withSuffix ? `${base} Risk` : base;
});

const classes = computed(() => {
  switch (normalized.value) {
    case 'critical':
      return 'border-rose-500 text-rose-700';
    case 'high':
      return 'border-orange-500 text-orange-700';
    case 'medium':
      return 'border-amber-500 text-amber-700';
    case 'low':
    default:
      return 'border-emerald-600 text-emerald-700';
  }
});

const dotClass = computed(() => {
  switch (normalized.value) {
    case 'critical':
      return 'bg-rose-500';
    case 'high':
      return 'bg-orange-500';
    case 'medium':
      return 'bg-amber-500';
    case 'low':
    default:
      return 'bg-emerald-600';
  }
});
</script>
