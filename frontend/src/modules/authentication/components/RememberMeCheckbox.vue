<template>
  <label class="inline-flex items-center gap-2 text-sm text-slate-600">
    <input
      type="checkbox"
      class="rounded border-slate-300 text-brand-600"
      :checked="modelValue"
      :disabled="disabled"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    Remember me
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>
