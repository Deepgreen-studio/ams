<template>
  <span
    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
    :class="system ? 'bg-amber-50 text-amber-800 ring-amber-600/20' : 'bg-brand-50 text-brand-700 ring-brand-600/20'"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: { type: String, default: '' },
  displayName: { type: String, default: '' },
  system: { type: Boolean, default: false },
});

const label = computed(() => props.displayName || props.name || 'Role');
</script>
