<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideApplication">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Application
        </label>
        <SelectBox
          v-model="form.application_id"
          wrapper-class="w-full"
          size="lg"
          placeholder="Select application"
          :options="applicationOptions"
          :disabled="loading || Boolean(initial.uuid)"
          @change="onApplicationChange"
        />
        <p v-if="errors.application_id" class="mt-1 text-xs text-rose-600">
          {{ errors.application_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Environment
        </label>
        <SelectBox
          v-model="form.application_environment_id"
          wrapper-class="w-full"
          size="lg"
          :options="environmentOptions"
          :disabled="loading || (!form.application_id && !initial.uuid)"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Integration
        </label>
        <SelectBox
          v-model="form.integration_id"
          wrapper-class="w-full"
          size="lg"
          :options="integrationOptions"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Owner contact
        </label>
        <SelectBox
          v-model="form.owner_contact_id"
          wrapper-class="w-full"
          size="lg"
          :options="contactOptions"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Ownership
        </label>
        <SelectBox
          v-model="form.ownership_type"
          wrapper-class="w-full"
          size="lg"
          :options="ownershipOptions"
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
          Activation date
        </label>
        <input
          v-model="form.activated_at"
          type="datetime-local"
          class="input"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Expiration date
        </label>
        <input
          v-model="form.expires_at"
          type="datetime-local"
          class="input"
          :disabled="loading"
        />
        <p v-if="errors.expires_at" class="mt-1 text-xs text-rose-600">
          {{ errors.expires_at[0] }}
        </p>
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
        :disabled="loading || (!hideApplication && !form.application_id)"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
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

const emit = defineEmits(['submit', 'cancel']);

const applications = ref([]);
const environments = ref([]);
const integrations = ref([]);
const contacts = ref([]);
const form = reactive(createForm(props.initial));

const ownershipOptions = [
  { value: 'customer_owned', label: 'Customer owned' },
  { value: 'platform_managed', label: 'Platform managed' },
  { value: 'shared', label: 'Shared' },
];

const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'active', label: 'Active' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'expired', label: 'Expired' },
  { value: 'cancelled', label: 'Cancelled' },
];

const applicationOptions = computed(() =>
  applications.value.map((app) => ({
    value: app.uuid,
    label: app.name,
  })),
);

const environmentOptions = computed(() => [
  { value: '', label: 'None' },
  ...environments.value.map((env) => ({
    value: env.uuid,
    label: `${env.name} (${env.type})`,
  })),
]);

const integrationOptions = computed(() => [
  { value: '', label: 'Use application default' },
  ...integrations.value.map((integration) => ({
    value: integration.uuid,
    label: integration.name,
  })),
]);

const contactOptions = computed(() => [
  { value: '', label: 'None' },
  ...contacts.value.map((contact) => ({
    value: contact.uuid,
    label: `${contact.name} (${contact.contact_type})`,
  })),
]);

watch(
  () => props.initial,
  async (value) => {
    Object.assign(form, createForm(value));
    if (form.application_id) {
      await loadEnvironments(form.application_id);
    }
  },
  { deep: true },
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
    environments.value = data.data?.environments?.items ?? data.data?.environments ?? [];
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

function onSubmit() {
  if (props.loading) return;
  if (!props.hideApplication && !form.application_id) return;
  emit('submit', { ...form });
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
