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
  status: { type: String, default: 'draft' },
});

const label = computed(() =>
  (props.status || 'draft').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
);

const classes = computed(() => {
  switch (props.status) {
    case 'production':
      return 'border-emerald-600 text-emerald-700';
    case 'beta':
      return 'border-sky-500 text-sky-700';
    case 'testing':
      return 'border-amber-500 text-amber-700';
    case 'deprecated':
      return 'border-slate-400 text-slate-600';
    case 'rollback':
      return 'border-rose-500 text-rose-700';
    case 'draft':
    default:
      return 'border-violet-500 text-violet-700';
  }
});

const dotClass = computed(() => {
  switch (props.status) {
    case 'production':
      return 'bg-emerald-600';
    case 'beta':
      return 'bg-sky-500';
    case 'testing':
      return 'bg-amber-500';
    case 'deprecated':
      return 'bg-slate-400';
    case 'rollback':
      return 'bg-rose-500';
    case 'draft':
    default:
      return 'bg-violet-500';
  }
});
</script>
