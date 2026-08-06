<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Plan type</label>
        <select v-model="form.plan_type" class="input" required>
          <option value="trial">Trial</option>
          <option value="monthly">Monthly</option>
          <option value="yearly">Yearly</option>
          <option value="lifetime">Lifetime</option>
          <option value="enterprise">Enterprise</option>
        </select>
        <p v-if="errors.plan_type" class="mt-1 text-xs text-rose-600">{{ errors.plan_type[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Plan name</label>
        <input v-model="form.plan_name" type="text" class="input" placeholder="Optional display name" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="">Auto</option>
          <option value="trialing">Trialing</option>
          <option value="active">Active</option>
          <option value="past_due">Past due</option>
          <option value="suspended">Suspended</option>
          <option value="cancelled">Cancelled</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Payment status</label>
        <select v-model="form.payment_status" class="input">
          <option value="">Auto</option>
          <option value="not_required">Not required</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="failed">Failed</option>
          <option value="past_due">Past due</option>
          <option value="refunded">Refunded</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Amount</label>
        <input v-model="form.amount" type="number" min="0" step="0.01" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Currency</label>
        <input v-model="form.currency" type="text" maxlength="3" class="input uppercase" placeholder="USD" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Starts at</label>
        <input v-model="form.starts_at" type="datetime-local" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Expires at</label>
        <input v-model="form.expires_at" type="datetime-local" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Renews at</label>
        <input v-model="form.renews_at" type="datetime-local" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Renewal reminder (days)</label>
        <input v-model="form.renewal_reminder_days" type="number" min="1" max="365" class="input" />
      </div>

      <div v-if="!initial.uuid" class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.issue_license" type="checkbox" class="rounded border-slate-300" />
          Automatically issue license key
        </label>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Features (comma separated)</label>
        <input v-model="featuresText" type="text" class="input" placeholder="dashboard, api_access, support" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea v-model="form.notes" rows="3" class="input" />
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Save subscription' },
});

defineEmits(['submit', 'cancel']);

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
    form.notes = value.notes || '';
    form.features = Array.isArray(value.features) ? value.features : [];
    featuresText.value = form.features.join(', ');
  },
  { immediate: true, deep: true }
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
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid rgb(203 213 225);
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: rgb(37 99 235);
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.2);
}
</style>
