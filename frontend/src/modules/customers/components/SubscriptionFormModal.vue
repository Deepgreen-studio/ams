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
      aria-labelledby="subscription-form-title"
    >
      <h3 id="subscription-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit subscription' : 'New subscription' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{
          isEdit
            ? 'Update plan, dates, and payment status.'
            : 'Create a subscription plan for this customer.'
        }}
      </p>

      <SubscriptionForm
        :key="subscription?.uuid || 'create'"
        class="mt-5"
        :initial="subscription || {}"
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
import SubscriptionForm from '@/modules/customers/components/SubscriptionForm.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  subscription: { type: Object, default: null },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const isEdit = computed(() => Boolean(props.subscription?.uuid));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Save changes' : 'Create subscription';
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
