<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-end gap-2 px-4 sm:px-6"
      aria-live="polite"
      aria-atomic="true"
    >
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl border shadow-lg"
          :class="toastClass(toast.type)"
        >
          <div class="flex items-start gap-3 px-4 py-3">
            <div class="min-w-0 flex-1">
              <p v-if="toast.title" class="text-sm font-semibold">{{ toast.title }}</p>
              <p class="text-sm" :class="toast.title ? 'mt-0.5 opacity-90' : ''">
                {{ toast.message }}
              </p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-md p-1 opacity-70 transition hover:opacity-100"
              aria-label="Dismiss notification"
              @click="dismissToast(toast.id)"
            >
              <span class="block text-lg leading-none">&times;</span>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast';

const { toasts, dismissToast } = useToast();

function toastClass(type) {
  if (type === 'error') {
    return 'border-rose-200 bg-rose-50 text-rose-800';
  }
  if (type === 'success') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-800';
  }
  return 'border-slate-200 bg-white text-slate-800';
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
