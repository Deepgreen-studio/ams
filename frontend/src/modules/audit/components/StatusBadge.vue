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

const raw = computed(() => String(props.status || 'info'));

const label = computed(() => {
  const value = raw.value;
  if (/^\d+$/.test(value)) {
    return value;
  }

  return value
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
});

const classes = computed(() => {
  const value = raw.value.toLowerCase();

  if (['get', 'head', 'options'].includes(value)) {
    return 'bg-sky-50 text-sky-700 ring-sky-600/20';
  }
  if (['post'].includes(value)) {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
  }
  if (['put', 'patch'].includes(value)) {
    return 'bg-amber-50 text-amber-800 ring-amber-600/20';
  }
  if (['delete'].includes(value)) {
    return 'bg-rose-50 text-rose-700 ring-rose-600/20';
  }
  if (/^2\d\d$/.test(value) || ['created', 'create', 'success', 'published', 'restored', 'info'].includes(value)) {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
  }
  if (['updated', 'update', 'edited', 'assigned'].includes(value)) {
    return 'bg-sky-50 text-sky-700 ring-sky-600/20';
  }
  if (/^5\d\d$/.test(value) || ['deleted', 'delete', 'destroyed', 'failed', 'error', 'unpublished', 'danger'].includes(value)) {
    return 'bg-rose-50 text-rose-700 ring-rose-600/20';
  }
  if (/^4\d\d$/.test(value) || ['warning', 'exported'].includes(value)) {
    return 'bg-amber-50 text-amber-800 ring-amber-600/20';
  }

  return 'bg-zinc-100 text-zinc-700 ring-zinc-500/15';
});
</script>
