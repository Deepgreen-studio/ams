<template>
  <label class="inline-flex items-center gap-2 text-sm text-zinc-600">
    <input
      type="checkbox"
      class="h-4 w-4 rounded border-zinc-300 text-brand-600 accent-brand-600"
      :checked="modelValue"
      :disabled="disabled"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    Remember Me
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>
