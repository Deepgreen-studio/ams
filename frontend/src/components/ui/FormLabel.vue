<template>
  <label :for="htmlFor" class="mb-1.5 block text-sm font-medium text-slate-700">
    <slot />
    <span v-if="required" class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
    <span v-if="required" class="sr-only"> (Required)</span>
    <span v-else-if="optional" class="font-normal text-slate-400"> (Optional)</span>
  </label>
</template>

<script setup>
defineProps({
  htmlFor: {
    type: String,
    default: undefined,
  },
  required: {
    type: Boolean,
    default: false,
  },
  optional: {
    type: Boolean,
    default: false,
  },
});
</script>
