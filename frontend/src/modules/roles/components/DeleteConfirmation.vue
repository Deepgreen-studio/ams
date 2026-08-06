<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="$emit('cancel')"
  >
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
      <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
      <p class="mt-2 text-sm text-slate-600">{{ message }}</p>
      <div class="mt-6 flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" :disabled="loading" @click="$emit('cancel')">
          Cancel
        </button>
        <button type="button" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60" :disabled="loading" @click="$emit('confirm')">
          {{ loading ? 'Processing...' : confirmLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Confirm' },
  message: { type: String, default: 'Are you sure?' },
  confirmLabel: { type: String, default: 'Confirm' },
  loading: { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);
</script>
