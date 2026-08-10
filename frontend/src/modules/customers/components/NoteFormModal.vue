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
      aria-labelledby="note-form-title"
    >
      <h3 id="note-form-title" class="text-lg font-semibold text-slate-900">Add note</h3>
      <p class="mt-1 text-sm text-slate-600">Capture a note or meeting summary for this customer.</p>

      <NoteForm
        class="mt-5"
        :loading="loading"
        :errors="errors"
        :error="error"
        :submit-label="loading ? 'Saving...' : 'Save note'"
        @submit="$emit('submit', $event)"
        @cancel="onCancel"
      />
    </div>
  </div>
</template>

<script setup>
import NoteForm from '@/modules/customers/components/NoteForm.vue';

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
