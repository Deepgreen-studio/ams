<template>
  <button
    type="button"
    class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60"
    :disabled="loading"
    @click="$emit('click')"
  >
    {{ loading ? 'Exporting…' : label }}
  </button>
</template>

<script setup>
defineProps({
  label: { type: String, default: 'Export CSV' },
  loading: { type: Boolean, default: false },
});
defineEmits(['click']);
</script>
