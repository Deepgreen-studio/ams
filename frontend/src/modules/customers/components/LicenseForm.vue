<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!initial.uuid" class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Subscription
        </label>
        <SelectBox
          v-model="form.subscription_id"
          wrapper-class="relative z-40 w-full"
          size="lg"
          placeholder="Select subscription"
          :options="subscriptionOptions"
          :disabled="loading || loadingOptions"
        />
        <p v-if="loadingOptions" class="mt-1 text-xs text-slate-500">Loading subscriptions...</p>
        <p v-else-if="loadError" class="mt-1 text-xs text-rose-600">{{ loadError }}</p>
        <p v-else-if="!subscriptionOptions.length" class="mt-1 text-xs text-amber-700">
          No subscriptions found for this customer. Create a subscription first.
        </p>
        <p v-if="errors.subscription_id" class="mt-1 text-xs text-rose-600">
          {{ errors.subscription_id[0] }}
        </p>
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
          Max activations
        </label>
        <input
          v-model="form.max_activations"
          type="number"
          min="1"
          class="input"
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

      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Features (comma separated)
        </label>
        <input v-model="featuresText" type="text" class="input" :disabled="loading" />
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
        :disabled="loading || (!initial.uuid && !form.subscription_id)"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { subscriptionService } from '@/modules/customers/services/subscriptionService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Save license' },
  defaultSubscriptionId: { type: String, default: '' },
  customerId: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'revoked', label: 'Revoked' },
  { value: 'expired', label: 'Expired' },
];

const subscriptions = ref([]);
const loadingOptions = ref(false);
const loadError = ref('');

const form = reactive({
  subscription_id: '',
  status: 'active',
  max_activations: 5,
  starts_at: '',
  expires_at: '',
  notes: '',
  features: [],
});

const featuresText = ref('');

const subscriptionOptions = computed(() =>
  subscriptions.value.map((item) => ({
    value: item.uuid,
    label: [
      item.plan_name || formatLabel(item.plan_type) || 'Subscription',
      item.status ? `(${formatLabel(item.status)})` : null,
    ]
      .filter(Boolean)
      .join(' '),
  })),
);

watch(
  () => [props.initial, props.defaultSubscriptionId],
  () => {
    const value = props.initial || {};
    form.subscription_id = value.subscription?.uuid || props.defaultSubscriptionId || '';
    form.status = value.status || 'active';
    form.max_activations = value.max_activations ?? 5;
    form.starts_at = toLocalInput(value.starts_at);
    form.expires_at = toLocalInput(value.expires_at);
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

watch(
  () => [props.customerId, props.initial?.uuid],
  ([customerId, licenseUuid]) => {
    if (licenseUuid || !customerId) {
      subscriptions.value = [];
      return;
    }
    loadSubscriptions(String(customerId));
  },
  { immediate: true },
);

async function loadSubscriptions(customerId) {
  loadingOptions.value = true;
  loadError.value = '';
  try {
    const { data } = await subscriptionService.list({
      customer: customerId,
      per_page: 100,
      sort_by: 'created_at',
      sort_dir: 'desc',
    });

    const items =
      data.data?.subscriptions?.items ??
      data.data?.subscriptions ??
      [];

    subscriptions.value = Array.isArray(items) ? items : [];

    if (
      props.defaultSubscriptionId &&
      !form.subscription_id &&
      subscriptions.value.some((item) => item.uuid === props.defaultSubscriptionId)
    ) {
      form.subscription_id = props.defaultSubscriptionId;
    }
  } catch (err) {
    subscriptions.value = [];
    loadError.value = err?.message || 'Unable to load subscriptions';
  } finally {
    loadingOptions.value = false;
  }
}

function formatLabel(value) {
  return String(value || '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function toLocalInput(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function onSubmit() {
  if (props.loading) return;
  if (!props.initial?.uuid && !form.subscription_id) return;
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
