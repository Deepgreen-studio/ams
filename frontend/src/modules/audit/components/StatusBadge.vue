<template>
  <span
    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
    :class="classes"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: [String, Number], default: '' },
});

const label = computed(() => String(props.status || 'info'));
const classes = computed(() => {
  const value = String(props.status || '').toLowerCase();
  if (['success', '200', '201', 'info'].includes(value)) {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
  }
  if (['failed', 'error', '500', 'danger'].includes(value)) {
    return 'bg-rose-50 text-rose-700 ring-rose-600/20';
  }
  if (['warning', '4', '401', '403', '404', '422'].some((x) => value.startsWith(x))) {
    return 'bg-amber-50 text-amber-800 ring-amber-600/20';
  }
  return 'bg-zinc-100 text-zinc-700 ring-zinc-500/15';
});
</script>
