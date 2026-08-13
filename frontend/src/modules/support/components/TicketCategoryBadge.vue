<template>
  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset bg-violet-50 text-violet-700 ring-violet-600/20">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  category: { type: String, default: '' },
  label: { type: String, default: '' },
});

const label = computed(
  () =>
    props.label ||
    (props.category || '').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
);
</script>
