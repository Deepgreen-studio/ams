<template>
  <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    <p class="text-sm text-slate-500">
      Showing
      <span class="font-medium text-slate-700">{{ meta?.from || 0 }}</span>
      to
      <span class="font-medium text-slate-700">{{ meta?.to || 0 }}</span>
      of
      <span class="font-medium text-slate-700">{{ meta?.total || 0 }}</span>
      results
    </p>

    <div class="flex items-center gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="!meta || meta.current_page <= 1 || loading"
        @click="$emit('change', meta.current_page - 1)"
      >
        Previous
      </button>
      <span class="text-sm text-slate-600">
        Page {{ meta?.current_page || 1 }} / {{ meta?.last_page || 1 }}
      </span>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="!meta || meta.current_page >= meta.last_page || loading"
        @click="$emit('change', meta.current_page + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  meta: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['change']);
</script>
