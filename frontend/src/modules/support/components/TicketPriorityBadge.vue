<template>
  <PriorityIndicator :priority="priority" :label="label" />
</template>

<script setup>
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';

defineProps({
  priority: { type: String, default: 'medium' },
  label: { type: String, default: '' },
});
</script>
