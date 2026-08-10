<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-[12px] bg-white p-6 shadow-xl sm:p-8"
      role="dialog"
      aria-modal="true"
      aria-labelledby="communication-form-title"
    >
      <h3 id="communication-form-title" class="text-lg font-semibold text-slate-900">
        Log communication
      </h3>
      <p class="mt-1 text-sm text-slate-600">Record an email, call, or meeting with this customer.</p>

      <CommunicationForm
        class="mt-5"
        :loading="loading"
        :errors="errors"
        :error="error"
        :submit-label="loading ? 'Saving...' : 'Log entry'"
        @submit="$emit('submit', $event)"
        @cancel="onCancel"
      />
    </div>
  </div>
</template>

<script setup>
import CommunicationForm from '@/modules/customers/components/CommunicationForm.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}
</script>
