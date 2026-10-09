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
        <FieldError :message="displayErrors.application_id?.[0] || ''" />
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
          Owner Contact
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
        <p class="mt-1 text-xs text-slate-500">{{ ownershipDescription }}</p>
        <p class="mt-1 text-xs text-slate-500">
          This is operational responsibility. The company remains the application owner.
        </p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Platform</label>
        <SelectBox v-model="form.platform" wrapper-class="w-full" size="lg" :options="platformOptions" :disabled="loading" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Version</label>
        <SelectBox v-model="form.application_version_id" wrapper-class="w-full" size="lg" :options="versionOptions" :disabled="loading || !form.application_id" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Build</label>
        <input v-model="form.build_label" type="text" class="input" :disabled="loading" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Release</label>
        <SelectBox v-model="form.application_release_id" wrapper-class="w-full" size="lg" :options="releaseOptions" :disabled="loading || !form.application_id" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Subscription</label>
        <SelectBox v-model="form.subscription_id" wrapper-class="w-full" size="lg" :options="subscriptionOptions" :disabled="loading" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">License</label>
        <SelectBox v-model="form.license_id" wrapper-class="w-full" size="lg" :options="licenseOptions" :disabled="loading" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">SLA</label>
        <SelectBox v-model="form.support_sla_policy_id" wrapper-class="w-full" size="lg" :options="slaOptions" :disabled="loading" />
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
          Activation Date
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
          Expiration Date
        </label>
        <input
          v-model="form.expires_at"
          type="datetime-local"
          class="input"
          :disabled="loading"
        />
        <FieldError :message="displayErrors.expires_at?.[0] || ''" />
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
import FieldError from '@/components/ui/FieldError.vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useFieldErrors } from '@/composables/useFieldErrors';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { applicationService } from '@/modules/applications/services/applicationService';
import { environmentService } from '@/modules/applications/services/environmentService';
import { versionService } from '@/modules/applications/services/versionService';
import { releaseService } from '@/modules/applications/services/releaseService';
import { integrationService } from '@/modules/integrations/services/integrationService';
import { customerContactService } from '@/modules/customers/services/customerContactService';
import { subscriptionService } from '@/modules/customers/services/subscriptionService';
import { licenseService } from '@/modules/customers/services/licenseService';
import { supportSlaService } from '@/modules/support/services/supportSlaService';
import { OWNERSHIP_OPTIONS } from '@/modules/customers/constants/customerModel';
import { toDateTimeLocalInput } from '@/utils/appTimezone';

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
const versions = ref([]);
const releases = ref([]);
const integrations = ref([]);
const contacts = ref([]);
const subscriptions = ref([]);
const licenses = ref([]);
const slaPolicies = ref([]);
const form = reactive(createForm(props.initial));
const { displayErrors } = useFieldErrors(form, () => props.errors);

const ownershipOptions = OWNERSHIP_OPTIONS.map((option) => ({ value: option.value, label: option.label }));
const ownershipDescription = computed(
  () => OWNERSHIP_OPTIONS.find((option) => option.value === form.ownership_type)?.description || '',
);

const platformOptions = [
  { value: '', label: 'Use application platform' },
  { value: 'android', label: 'Android' },
  { value: 'ios', label: 'iOS' },
  { value: 'web', label: 'Web' },
  { value: 'desktop', label: 'Desktop' },
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

const versionOptions = computed(() => [
  { value: '', label: 'None' },
  ...versions.value.map((version) => ({
    value: version.uuid,
    label: version.version_number,
  })),
]);

const releaseOptions = computed(() => [
  { value: '', label: 'None' },
  ...releases.value.map((release) => ({
    value: release.uuid,
    label: release.name || release.version_label,
  })),
]);

const subscriptionOptions = computed(() => [
  { value: '', label: 'None' },
  ...subscriptions.value.map((subscription) => ({
    value: subscription.uuid,
    label: subscription.plan_name,
  })),
]);

const licenseOptions = computed(() => [
  { value: '', label: 'None' },
  ...licenses.value.map((license) => ({
    value: license.uuid,
    label: license.status,
  })),
]);

const slaOptions = computed(() => [
  { value: '', label: 'None' },
  ...slaPolicies.value.map((policy) => ({
    value: policy.uuid,
    label: policy.name,
  })),
]);

watch(
  () => props.initial,
  async (value) => {
    Object.assign(form, createForm(value));
    if (form.application_id) {
      await loadApplicationContext(form.application_id);
    }
  },
  { deep: true },
);

onMounted(async () => {
  await Promise.all([loadApplications(), loadIntegrations(), loadContacts(), loadCommercial(), loadSla()]);
  if (form.application_id) {
    await loadApplicationContext(form.application_id);
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

async function loadApplicationContext(applicationId) {
  await Promise.all([loadEnvironments(applicationId), loadVersions(applicationId), loadReleases(applicationId)]);
}

async function loadVersions(applicationId) {
  if (!applicationId) {
    versions.value = [];
    return;
  }
  try {
    const { data } = await versionService.list(applicationId, { per_page: 100 });
    versions.value = data.data?.versions?.items ?? data.data?.versions ?? [];
  } catch {
    versions.value = [];
  }
}

async function loadReleases(applicationId) {
  if (!applicationId) {
    releases.value = [];
    return;
  }
  try {
    const { data } = await releaseService.list(applicationId, { per_page: 100 });
    releases.value = data.data?.releases?.items ?? data.data?.releases ?? [];
  } catch {
    releases.value = [];
  }
}

async function loadCommercial() {
  try {
    const [subscriptionResponse, licenseResponse] = await Promise.all([
      subscriptionService.list({ customer: props.customerId, per_page: 100 }),
      licenseService.list({ customer: props.customerId, per_page: 100 }),
    ]);
    subscriptions.value = subscriptionResponse.data.data?.subscriptions?.items ?? [];
    licenses.value = licenseResponse.data.data?.licenses?.items ?? [];
  } catch {
    subscriptions.value = [];
    licenses.value = [];
  }
}

async function loadSla() {
  try {
    const params = { per_page: 100 };
    if (props.companyId) params.company = props.companyId;
    const { data } = await supportSlaService.policies(params);
    slaPolicies.value = data.data?.policies?.items ?? [];
  } catch {
    slaPolicies.value = [];
  }
}

async function onApplicationChange() {
  form.application_environment_id = '';
  form.application_version_id = '';
  form.application_release_id = '';
  await loadApplicationContext(form.application_id);
}

function toLocalInput(value) {
  return toDateTimeLocalInput(value);
}

function createForm(value = {}) {
  return {
    application_id: value.application?.uuid || value.application_id || '',
    application_environment_id: value.environment?.uuid || value.application_environment_id || '',
    integration_id: value.integration?.uuid || value.integration_id || '',
    owner_contact_id: value.owner_contact?.uuid || value.owner_contact_id || '',
    platform: value.platform || '',
    application_version_id: value.version?.uuid || value.application_version_id || '',
    build_label: value.build_label || '',
    application_release_id: value.release?.uuid || value.application_release_id || '',
    subscription_id: value.subscriptions?.[0]?.uuid || value.subscription_id || '',
    license_id: value.licenses?.[0]?.uuid || value.license_id || '',
    support_sla_policy_id: value.sla_policy?.uuid || value.support_sla_policy_id || '',
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
