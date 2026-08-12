<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="$emit('close')"
    >
      <div
        class="flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-zinc-100"
        role="dialog"
        aria-modal="true"
        @click.stop
      >
        <div class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <div class="min-w-0">
            <h3 class="truncate text-base font-semibold text-slate-900">{{ title }}</h3>
            <p v-if="subtitle" class="mt-0.5 truncate text-xs text-slate-500">{{ subtitle }}</p>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-[12px] border border-zinc-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-zinc-50"
            @click="$emit('close')"
          >
            Close
          </button>
        </div>

        <div class="overflow-y-auto px-6 py-5">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Details' },
  subtitle: { type: String, default: '' },
});

defineEmits(['close']);
</script>
