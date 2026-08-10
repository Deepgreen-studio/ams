<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[12px] bg-white p-6 shadow-xl sm:p-8"
      role="dialog"
      aria-modal="true"
      aria-labelledby="contact-form-title"
    >
      <h3 id="contact-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit contact' : 'Add contact' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{
          isEdit
            ? 'Update contact details and classification.'
            : 'Create a contact for this customer.'
        }}
      </p>

      <ContactForm
        :key="contact?.uuid || 'create'"
        class="mt-5"
        :initial="contact || {}"
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
import { computed } from 'vue';
import ContactForm from '@/modules/customers/components/ContactForm.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  contact: {
    type: Object,
    default: null,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  error: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['submit', 'cancel']);

const isEdit = computed(() => Boolean(props.contact?.uuid));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Save changes' : 'Create contact';
});

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit(payload) {
  if (props.loading) return;
  emit('submit', payload);
}
</script>
