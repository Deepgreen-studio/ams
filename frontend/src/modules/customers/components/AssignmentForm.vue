<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideApplication">
        <label class="mb-1 block text-sm font-medium text-slate-700">Application</label>
        <select v-model="form.application_id" class="input" required :disabled="Boolean(initial.uuid)" @change="onApplicationChange">
          <option value="" disabled>Select application</option>
          <option v-for="app in applications" :key="app.uuid" :value="app.uuid">
            {{ app.name }}
          </option>
        </select>
        <p v-if="errors.application_id" class="mt-1 text-xs text-rose-600">{{ errors.application_id[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Environment</label>
        <select v-model="form.application_environment_id" class="input" :disabled="!form.application_id && !initial.uuid">
          <option value="">None</option>
          <option v-for="env in environments" :key="env.uuid" :value="env.uuid">
            {{ env.name }} ({{ env.type }})
          </option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Integration</label>
        <select v-model="form.integration_id" class="input">
          <option value="">Use application default</option>
          <option v-for="integration in integrations" :key="integration.uuid" :value="integration.uuid">
            {{ integration.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Owner contact</label>
        <select v-model="form.owner_contact_id" class="input">
          <option value="">None</option>
          <option v-for="contact in contacts" :key="contact.uuid" :value="contact.uuid">
            {{ contact.name }} ({{ contact.contact_type }})
          </option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Ownership</label>
        <select v-model="form.ownership_type" class="input" required>
          <option value="customer_owned">Customer owned</option>
          <option value="platform_managed">Platform managed</option>
          <option value="shared">Shared</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input" required>
          <option value="pending">Pending</option>
          <option value="active">Active</option>
          <option value="suspended">Suspended</option>
          <option value="expired">Expired</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Activation date</label>
        <input v-model="form.activated_at" type="datetime-local" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Expiration date</label>
        <input v-model="form.expires_at" type="datetime-local" class="input" />
        <p v-if="errors.expires_at" class="mt-1 text-xs text-rose-600">{{ errors.expires_at[0] }}</p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea v-model="form.notes" rows="3" class="input" />
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('cancel')"
      >
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
import { onMounted, reactive, ref, watch } from 'vue';
import { applicationService } from '@/modules/applications/services/applicationService';
import { environmentService } from '@/modules/applications/services/environmentService';
import { integrationService } from '@/modules/integrations/services/integrationService';
import { customerContactService } from '@/modules/customers/services/customerContactService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  customerId: { type: String, required: true },
  companyId: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideApplication: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel']);

const applications = ref([]);
const environments = ref([]);
const integrations = ref([]);
const contacts = ref([]);
const form = reactive(createForm(props.initial));

watch(
  () => props.initial,
  async (value) => {
    Object.assign(form, createForm(value));
    if (form.application_id) {
      await loadEnvironments(form.application_id);
    }
  },
  { deep: true }
);

onMounted(async () => {
  await Promise.all([loadApplications(), loadIntegrations(), loadContacts()]);
  if (form.application_id) {
    await loadEnvironments(form.application_id);
  }
});

async function loadApplications() {
  try {
    const params = { per_page: 100, sort_by: 'name', sort_dir: 'asc' };
    if (props.companyId) {
      params.company = props.companyId;
    }
    const { data } = await applicationService.list(params);
    applications.value = data.data?.applications?.items ?? [];
  } catch {
    applications.value = [];
  }
}

async function loadIntegrations() {
  try {
    const params = { per_page: 100 };
    if (props.companyId) {
      params.company = props.companyId;
    }
    const { data } = await integrationService.list(params);
    integrations.value = data.data?.integrations?.items ?? [];
  } catch {
    integrations.value = [];
  }
}

async function loadContacts() {
  try {
    const { data } = await customerContactService.list({
      customer: props.customerId,
      per_page: 100,
      status: 'active',
    });
    contacts.value = data.data?.contacts?.items ?? [];
  } catch {
    contacts.value = [];
  }
}

async function loadEnvironments(applicationId) {
  if (!applicationId) {
    environments.value = [];
    return;
  }

  try {
    const { data } = await environmentService.list(applicationId, { per_page: 100 });
    environments.value = data.data?.environments?.items
      ?? data.data?.environments
      ?? [];
  } catch {
    environments.value = [];
  }
}

async function onApplicationChange() {
  form.application_environment_id = '';
  await loadEnvironments(form.application_id);
}

function toLocalInput(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function createForm(value = {}) {
  return {
    application_id: value.application?.uuid || value.application_id || '',
    application_environment_id: value.environment?.uuid || value.application_environment_id || '',
    integration_id: value.integration?.uuid || value.integration_id || '',
    owner_contact_id: value.owner_contact?.uuid || value.owner_contact_id || '',
    ownership_type: value.ownership_type || 'customer_owned',
    status: value.status || 'pending',
    activated_at: toLocalInput(value.activated_at),
    expires_at: toLocalInput(value.expires_at),
    notes: value.notes || '',
  };
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
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
