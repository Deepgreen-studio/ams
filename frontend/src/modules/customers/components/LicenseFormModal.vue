<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-visible rounded-[12px] bg-white shadow-xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="license-form-title"
    >
      <div class="shrink-0 border-b border-zinc-100 px-6 pb-4 pt-6 sm:px-8">
        <h3 id="license-form-title" class="text-lg font-semibold text-slate-900">
          {{ isEdit ? 'Edit license' : 'Issue license' }}
        </h3>
        <p class="mt-1 text-sm text-slate-600">
          {{
            isEdit
              ? 'Update license status, activations, and dates.'
              : 'Create a license key for this customer.'
          }}
        </p>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto overflow-x-visible px-6 py-5 sm:px-8">
        <LicenseForm
          :key="license?.uuid || `create-${defaultSubscriptionId || 'new'}`"
          :initial="license || {}"
          :customer-id="customerId"
          :default-subscription-id="defaultSubscriptionId"
          :loading="loading"
          :errors="errors"
          :error="error"
          :submit-label="submitLabel"
          @submit="onSubmit"
          @cancel="onCancel"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import LicenseForm from '@/modules/customers/components/LicenseForm.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  license: { type: Object, default: null },
  customerId: { type: String, required: true },
  defaultSubscriptionId: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const isEdit = computed(() => Boolean(props.license?.uuid));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Save changes' : 'Issue license';
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
