<template>
  <button
    type="button"
    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
    :disabled="loading"
    @click="$emit('click')"
  >
    {{ loading ? 'Exporting...' : label }}
  </button>
</template>

<script setup>
defineProps({
  label: { type: String, default: 'Export CSV' },
  loading: { type: Boolean, default: false },
});
defineEmits(['click']);
</script>
