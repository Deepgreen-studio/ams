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
      aria-labelledby="assignment-form-title"
    >
      <h3 id="assignment-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit assignment' : 'Assign application' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{
          isEdit
            ? 'Update ownership, environment, status, and dates.'
            : 'Link an application to this customer.'
        }}
      </p>

      <AssignmentForm
        :key="assignment?.uuid || 'create'"
        class="mt-5"
        :initial="assignment || {}"
        :customer-id="customerId"
        :company-id="companyId"
        :loading="loading"
        :errors="errors"
        :error="error"
        :hide-application="isEdit"
        :submit-label="submitLabel"
        @submit="onSubmit"
        @cancel="onCancel"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AssignmentForm from '@/modules/customers/components/AssignmentForm.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  assignment: {
    type: Object,
    default: null,
  },
  customerId: {
    type: String,
    required: true,
  },
  companyId: {
    type: String,
    default: '',
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

const isEdit = computed(() => Boolean(props.assignment?.uuid));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Save changes' : 'Assign application';
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
