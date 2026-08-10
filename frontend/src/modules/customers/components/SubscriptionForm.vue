<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Plan type
        </label>
        <SelectBox
          v-model="form.plan_type"
          wrapper-class="w-full"
          size="lg"
          :options="planTypeOptions"
          :disabled="loading"
        />
        <p v-if="errors.plan_type" class="mt-1 text-xs text-rose-600">{{ errors.plan_type[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Plan name
        </label>
        <input
          v-model="form.plan_name"
          type="text"
          class="input"
          placeholder="Optional display name"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Status
        </label>
        <SelectBox
          v-model="form.status"
          wrapper-class="w-full"
          size="lg"
          :options="statusOptions"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Payment status
        </label>
        <SelectBox
          v-model="form.payment_status"
          wrapper-class="w-full"
          size="lg"
          :options="paymentOptions"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Amount
        </label>
        <input
          v-model="form.amount"
          type="number"
          min="0"
          step="0.01"
          class="input"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Currency
        </label>
        <input
          v-model="form.currency"
          type="text"
          maxlength="3"
          class="input uppercase"
          placeholder="USD"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Starts at
        </label>
        <input v-model="form.starts_at" type="datetime-local" class="input" :disabled="loading" />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Expires at
        </label>
        <input v-model="form.expires_at" type="datetime-local" class="input" :disabled="loading" />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Renews at
        </label>
        <input v-model="form.renews_at" type="datetime-local" class="input" :disabled="loading" />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Renewal reminder (days)
        </label>
        <input
          v-model="form.renewal_reminder_days"
          type="number"
          min="1"
          max="365"
          class="input"
          :disabled="loading"
        />
      </div>

      <div v-if="!initial.uuid" class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input
            v-model="form.issue_license"
            type="checkbox"
            class="rounded border-zinc-300"
            :disabled="loading"
          />
          Automatically issue license key
        </label>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Features (comma separated)
        </label>
        <input
          v-model="featuresText"
          type="text"
          class="input"
          placeholder="dashboard, api_access, support"
          :disabled="loading"
        />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Notes
        </label>
        <textarea v-model="form.notes" rows="3" class="input" :disabled="loading" />
      </div>
    </div>

    <div class="flex justify-end gap-2 pt-1">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Save subscription' },
});

const emit = defineEmits(['submit', 'cancel']);

const planTypeOptions = [
  { value: 'trial', label: 'Trial' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'yearly', label: 'Yearly' },
  { value: 'lifetime', label: 'Lifetime' },
  { value: 'enterprise', label: 'Enterprise' },
];

const statusOptions = [
  { value: '', label: 'Auto' },
  { value: 'trialing', label: 'Trialing' },
  { value: 'active', label: 'Active' },
  { value: 'past_due', label: 'Past due' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'expired', label: 'Expired' },
];

const paymentOptions = [
  { value: '', label: 'Auto' },
  { value: 'not_required', label: 'Not required' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'failed', label: 'Failed' },
  { value: 'past_due', label: 'Past due' },
  { value: 'refunded', label: 'Refunded' },
];

const form = reactive({
  plan_type: 'monthly',
  plan_name: '',
  status: '',
  payment_status: '',
  amount: '',
  currency: 'USD',
  starts_at: '',
  expires_at: '',
  renews_at: '',
  renewal_reminder_days: 14,
  issue_license: true,
  notes: '',
  features: [],
});

const featuresText = ref('');

watch(
  () => props.initial,
  (value) => {
    if (!value) return;
    form.plan_type = value.plan_type || 'monthly';
    form.plan_name = value.plan_name || '';
    form.status = value.status || '';
    form.payment_status = value.payment_status || '';
    form.amount = value.amount ?? '';
    form.currency = value.currency || 'USD';
    form.starts_at = toLocalInput(value.starts_at);
    form.expires_at = toLocalInput(value.expires_at);
    form.renews_at = toLocalInput(value.renews_at);
    form.renewal_reminder_days = value.renewal_reminder_days ?? 14;
    form.issue_license = value.issue_license ?? true;
    form.notes = value.notes || '';
    form.features = Array.isArray(value.features) ? value.features : [];
    featuresText.value = form.features.join(', ');
  },
  { immediate: true, deep: true },
);

watch(featuresText, (value) => {
  form.features = value
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);
});

function toLocalInput(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function onSubmit() {
  if (props.loading) return;
  emit('submit', { ...form, features: [...form.features] });
}
</script>

<style scoped>
.input {
  width: 100%;
  height: 3rem;
  border-radius: 12px;
  border: 1px solid #e4e4e7;
  background: #fff;
  padding: 0.5rem 0.875rem;
  font-size: 0.875rem;
  color: #1e293b;
  outline: none;
  box-shadow: none;
}
textarea.input {
  height: auto;
  min-height: 5rem;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}
.input:focus {
  border-color: var(--color-brand-500, #f97316);
}
.input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
