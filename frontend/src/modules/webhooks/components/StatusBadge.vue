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
  status: {
    type: String,
    default: 'active',
  },
  kind: {
    type: String,
    default: 'webhook',
  },
});

const label = computed(() => {
  const value = props.status || (props.kind === 'delivery' ? 'pending' : 'active');
  return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
});

const classes = computed(() => {
  if (props.kind === 'delivery') {
    switch (props.status) {
      case 'success':
        return 'border-emerald-600 text-emerald-700';
      case 'failed':
        return 'border-rose-500 text-rose-700';
      case 'retrying':
        return 'border-amber-500 text-amber-700';
      case 'processing':
        return 'border-sky-600 text-sky-700';
      case 'pending':
      default:
        return 'border-slate-400 text-slate-600';
    }
  }

  switch (props.status) {
    case 'active':
      return 'border-emerald-600 text-emerald-700';
    case 'paused':
      return 'border-amber-500 text-amber-700';
    case 'disabled':
      return 'border-rose-500 text-rose-700';
    case 'inactive':
    default:
      return 'border-slate-400 text-slate-600';
  }
});

const dotClass = computed(() => {
  if (props.kind === 'delivery') {
    switch (props.status) {
      case 'success':
        return 'bg-emerald-600';
      case 'failed':
        return 'bg-rose-500';
      case 'retrying':
        return 'bg-amber-500';
      case 'processing':
        return 'bg-sky-600';
      case 'pending':
      default:
        return 'bg-slate-400';
    }
  }

  switch (props.status) {
    case 'active':
      return 'bg-emerald-600';
    case 'paused':
      return 'bg-amber-500';
    case 'disabled':
      return 'bg-rose-500';
    case 'inactive':
    default:
      return 'bg-slate-400';
  }
});
</script>
