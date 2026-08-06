<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!initial.uuid">
        <label class="mb-1 block text-sm font-medium text-slate-700">Subscription UUID</label>
        <input v-model="form.subscription_id" type="text" class="input" required placeholder="Linked subscription" />
        <p v-if="errors.subscription_id" class="mt-1 text-xs text-rose-600">{{ errors.subscription_id[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input" required>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="revoked">Revoked</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Max activations</label>
        <input v-model="form.max_activations" type="number" min="1" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Starts at</label>
        <input v-model="form.starts_at" type="datetime-local" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Expires at</label>
        <input v-model="form.expires_at" type="datetime-local" class="input" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Features (comma separated)</label>
        <input v-model="featuresText" type="text" class="input" />
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
  submitLabel: { type: String, default: 'Save license' },
  defaultSubscriptionId: { type: String, default: '' },
});

defineEmits(['submit', 'cancel']);

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
