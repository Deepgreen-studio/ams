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
      <h3 id="note-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit note' : 'Add note' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{
          isEdit
            ? 'Update this note or meeting summary.'
            : 'Capture a note or meeting summary for this customer.'
        }}
      </p>

      <NoteForm
        :key="formKey"
        class="mt-5"
        :initial="formInitial"
        :loading="loading"
        :errors="errors"
        :error="error"
        :submit-label="submitLabel"
        @submit="onSubmit"
        @cancel="onCancel"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import NoteForm from '@/modules/customers/components/NoteForm.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  note: { type: Object, default: null },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const formInitial = ref({
  note_type: 'general',
  title: '',
  body: '',
  is_pinned: false,
});

const isEdit = computed(() => Boolean(props.note?.uuid));
const formKey = computed(() => `${props.open ? 'open' : 'closed'}-${props.note?.uuid || 'create'}`);

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Save changes' : 'Save note';
});

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return;
    formInitial.value = {
      note_type: props.note?.note_type || 'general',
      title: props.note?.title || '',
      body: props.note?.body || '',
      is_pinned: Boolean(props.note?.is_pinned),
    };
  },
);

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit(payload) {
  emit('submit', payload);
}
</script>
