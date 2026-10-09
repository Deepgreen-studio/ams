<template>
  <p
    class="mt-1 h-4 truncate text-xs leading-4 text-rose-600"
    :title="message || undefined"
  >
    {{ message }}
  </p>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: '',
  },
});
</script>
