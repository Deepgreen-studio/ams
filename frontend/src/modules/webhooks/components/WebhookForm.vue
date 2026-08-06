<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required>
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">{{ company.company_name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input v-model="form.name" type="text" class="input" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Direction</label>
        <select v-model="form.direction" class="input" :disabled="Boolean(initial.uuid)">
          <option value="outgoing">Outgoing</option>
          <option value="incoming">Incoming</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="paused">Paused</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>
      <div v-if="form.direction === 'outgoing'" class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Destination URL</label>
        <input v-model="form.url" type="url" class="input" placeholder="https://" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Signature algorithm</label>
        <select v-model="form.signature_algorithm" class="input">
          <option value="hmac_sha256">HMAC SHA-256</option>
          <option value="hmac_sha1">HMAC SHA-1</option>
          <option value="none">None</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Signature header</label>
        <input v-model="form.signature_header" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timeout (seconds)</label>
        <input v-model.number="form.timeout" type="number" min="1" max="300" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Retry attempts</label>
        <input v-model.number="form.retry_attempts" type="number" min="1" max="10" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Subscribed events (comma-separated)</label>
        <input v-model="eventsText" type="text" class="input" placeholder="webhook.test, integration.created" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="3" class="input" />
      </div>
      <div v-if="initial.uuid" class="md:col-span-2">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.rotate_secret" type="checkbox" class="rounded border-slate-300" />
          Rotate webhook secret
        </label>
      </div>
    </div>
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">Cancel</button>
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideCompany: { type: Boolean, default: false },
});
const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const eventsText = ref('');
const form = reactive(createForm(props.initial));

watch(() => props.initial, (value) => {
  Object.assign(form, createForm(value));
  eventsText.value = (value.subscribed_events || []).join(', ');
}, { deep: true, immediate: true });

onMounted(async () => {
  if (props.hideCompany || props.initial?.uuid) return;
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    name: value.name || '',
    direction: value.direction || 'outgoing',
    status: value.status || 'inactive',
    url: value.url || '',
    description: value.description || '',
    signature_algorithm: value.signature_algorithm || 'hmac_sha256',
    signature_header: value.signature_header || 'X-AMS-Signature',
    timeout: value.timeout ?? 30,
    retry_attempts: value.retry_attempts ?? 3,
    rotate_secret: false,
  };
}

function onSubmit() {
  const subscribed_events = eventsText.value
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

  emit('submit', {
    ...form,
    subscribed_events,
    url: form.direction === 'outgoing' ? form.url : null,
  });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
  background: white;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
