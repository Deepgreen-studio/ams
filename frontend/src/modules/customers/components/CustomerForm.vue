<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div v-if="!hideCompany">
        <FormLabel required>Owning Company</FormLabel>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companyOptions"
          :disabled="Boolean(initial.uuid)"
          :error="Boolean(displayErrors.company_id)"
          @change="onCompanyChange"
        />
        <p class="mt-1 text-xs text-slate-500">The company owns the applications. This customer is entitled to use them.</p>
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
        <SelectBox
          v-model="form.location_id"
          size="lg"
          :placeholder="form.company_id ? 'Select a location' : 'Select a company first'"
          :options="locationOptions"
          :disabled="!form.company_id"
          :error="Boolean(displayErrors.location_id)"
        />
        <p class="mt-1 text-xs text-slate-500">Active locations for the selected company.</p>
        <p v-if="displayErrors.location_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.location_id[0] }}
        </p>
      </div>

      <div>
        <FormLabel required>Customer Type</FormLabel>
        <SelectBox
          v-model="form.customer_type"
          size="lg"
          :options="typeOptions"
          :error="Boolean(displayErrors.customer_type)"
        />
        <p v-if="displayErrors.customer_type" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.customer_type[0] }}
        </p>
      </div>

      <div v-if="initial.uuid">
        <FormLabel>Customer ID</FormLabel>
        <input
          :value="initial.customer_number || ''"
          type="text"
          readonly
          class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-700"
        />
        <p class="mt-1 text-xs text-slate-500">Immutable reference. UUID remains the internal identifier.</p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Business reference</label>
        <input
          v-model="form.reference"
          type="text"
          placeholder="Optional account or contract reference"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('reference')"
        />
        <p v-if="displayErrors.reference" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.reference[0] }}
        </p>
      </div>

      <template v-if="isIndividual">
        <div>
          <FormLabel required>First Name</FormLabel>
          <input v-model="form.first_name" type="text" class="field" :class="fieldClass('first_name')" />
          <p v-if="displayErrors.first_name" class="mt-1 text-xs text-rose-600">{{ displayErrors.first_name[0] }}</p>
        </div>
        <div>
          <FormLabel required>Last Name</FormLabel>
          <input v-model="form.last_name" type="text" class="field" :class="fieldClass('last_name')" />
          <p v-if="displayErrors.last_name" class="mt-1 text-xs text-rose-600">{{ displayErrors.last_name[0] }}</p>
        </div>
      </template>

      <template v-else>
        <div class="md:col-span-2">
          <FormLabel required>Organization Name</FormLabel>
          <input v-model="form.company_name" type="text" class="field" :class="fieldClass('company_name')" />
          <p v-if="displayErrors.company_name" class="mt-1 text-xs text-rose-600">{{ displayErrors.company_name[0] }}</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Legal Name</label>
          <input v-model="form.legal_name" type="text" class="field" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Registration / Tax ID</label>
          <input v-model="form.registration_number" type="text" class="field" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Primary Contact</label>
          <input v-model="form.primary_contact_name" type="text" class="field" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Primary Contact Title</label>
          <input v-model="form.primary_contact_title" type="text" class="field" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Primary Contact Email</label>
          <input v-model="form.primary_contact_email" type="email" class="field" :class="fieldClass('primary_contact_email')" />
          <p v-if="displayErrors.primary_contact_email" class="mt-1 text-xs text-rose-600">
            {{ displayErrors.primary_contact_email[0] }}
          </p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Primary Contact Phone</label>
          <PhoneInput v-model="form.primary_contact_phone" :error="Boolean(displayErrors.primary_contact_phone)" />
          <p v-if="displayErrors.primary_contact_phone" class="mt-1 text-xs text-rose-600">
            {{ displayErrors.primary_contact_phone[0] }}
          </p>
        </div>
      </template>

      <div>
        <FormLabel required>Email</FormLabel>
        <input v-model="form.email" type="email" class="field" :class="fieldClass('email')" />
        <p v-if="displayErrors.email" class="mt-1 text-xs text-rose-600">{{ displayErrors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
        <PhoneInput v-model="form.phone" :error="Boolean(displayErrors.phone)" />
        <p v-if="displayErrors.phone" class="mt-1 text-xs text-rose-600">{{ displayErrors.phone[0] }}</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Website</label>
        <input
          v-model="form.website"
          type="url"
          placeholder="https://"
          class="field"
          :class="fieldClass('website')"
        />
        <p class="mt-1 text-xs text-slate-500">Optional. Use a full http or https URL.</p>
        <p v-if="displayErrors.website" class="mt-1 text-xs text-rose-600">{{ displayErrors.website[0] }}</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Industry</label>
        <SelectBox
          v-model="form.industry_id"
          size="lg"
          placeholder="Select industry"
          :options="industryOptions"
          :error="Boolean(displayErrors.industry_id)"
        />
        <p v-if="displayErrors.industry_id" class="mt-1 text-xs text-rose-600">{{ displayErrors.industry_id[0] }}</p>
      </div>
      <div v-if="subIndustryOptions.length > 1">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Sub-industry</label>
        <SelectBox v-model="form.sub_industry_id" size="lg" :options="subIndustryOptions" />
      </div>
      <div v-if="selectedIndustry?.is_other">
        <FormLabel required>Other industry</FormLabel>
        <input v-model="form.industry_other" type="text" class="field" :class="fieldClass('industry_other')" />
        <p v-if="displayErrors.industry_other" class="mt-1 text-xs text-rose-600">{{ displayErrors.industry_other[0] }}</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>

      <div class="md:col-span-2 rounded-xl border border-slate-200 p-4">
        <h3 class="text-sm font-semibold text-slate-900">Privacy</h3>
        <p class="mt-1 text-xs text-slate-500">
          Legal basis and purpose are required. After the retention date, identifying data is anonymized. A confirmed deletion request does the same, unless a legal obligation is still in force.
        </p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Legal basis</label>
            <SelectBox v-model="form.legal_basis" size="lg" :options="legalBasisOptions" :class="fieldClass('legal_basis')" />
            <p v-if="displayErrors.legal_basis" class="mt-1 text-xs text-rose-600">{{ displayErrors.legal_basis[0] }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Retain until</label>
            <input v-model="form.retention_until" type="date" class="field" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Processing purpose</label>
            <textarea v-model="form.processing_purpose" rows="2" class="area" :class="fieldClass('processing_purpose')" />
            <p v-if="displayErrors.processing_purpose" class="mt-1 text-xs text-rose-600">{{ displayErrors.processing_purpose[0] }}</p>
          </div>
        </div>
      </div>

      <div v-if="!initial.uuid" class="md:col-span-2 rounded-xl border border-slate-200 p-4">
        <h3 class="text-sm font-semibold text-slate-900">Application entitlement</h3>
        <p class="mt-1 text-xs text-slate-500">
          Optional. Assign an application the company already owns. This does not create or transfer the application.
          You can also assign applications after the customer is created.
        </p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Application</label>
            <SelectBox
              v-model="form.application_id"
              size="lg"
              placeholder="Assign later"
              :options="applicationOptions"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Environment</label>
            <SelectBox v-model="form.application_environment_id" size="lg" :options="environmentOptions" />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Operational responsibility</label>
            <SelectBox v-model="form.ownership_type" size="lg" :options="ownershipOptions" />
            <p class="mt-1 text-xs text-slate-500">{{ ownershipDescription }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Notes</label>
        <textarea v-model="form.notes" rows="3" class="area" />
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
      <button
        type="button"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FormLabel from '@/components/ui/FormLabel.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';
import { applicationService } from '@/modules/applications/services/applicationService';
import { environmentService } from '@/modules/applications/services/environmentService';
import { customerService } from '@/modules/customers/services/customerService';
import { LEGAL_BASIS_OPTIONS, OWNERSHIP_OPTIONS } from '@/modules/customers/constants/customerModel';
import { isValidE164, PHONE_INVALID_MESSAGE } from '@/utils/phone';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideCompany: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();
const companies = ref([]);
const locationItems = ref([]);
const industries = ref([]);
const applications = ref([]);
const environments = ref([]);
const localErrors = ref({});
const form = reactive(createForm(props.initial));

const typeOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'business', label: 'Organization — Business' },
  { value: 'enterprise', label: 'Organization — Enterprise' },
];

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

const legalBasisOptions = LEGAL_BASIS_OPTIONS;
const ownershipOptions = OWNERSHIP_OPTIONS.map((option) => ({ value: option.value, label: option.label }));

const isIndividual = computed(() => form.customer_type === 'individual');

const locationOptions = computed(() => [
  { value: '', label: 'Not specified' },
  ...locationItems.value
    .filter((location) => location.status === 'active' || location.uuid === form.location_id)
    .map((location) => ({
      value: location.uuid,
      label: location.branch_name || location.uuid,
    })),
]);

const companyOptions = computed(() =>
  companies.value
    .filter((company) => company.status === 'active' || company.uuid === form.company_id)
    .map((company) => ({
      value: company.uuid,
      label: company.company_name,
    })),
);

const industryOptions = computed(() => [
  { value: '', label: 'Not specified' },
  ...industries.value.map((industry) => ({
    value: industry.uuid,
    label: industry.name,
  })),
]);

const selectedIndustry = computed(() =>
  industries.value.find((industry) => industry.uuid === form.industry_id) || null,
);

const subIndustryOptions = computed(() => [
  { value: '', label: 'None' },
  ...(selectedIndustry.value?.children || []).map((industry) => ({
    value: industry.uuid,
    label: industry.name,
  })),
]);

const applicationOptions = computed(() => [
  { value: '', label: 'Assign later' },
  ...applications.value.map((app) => ({ value: app.uuid, label: app.name })),
]);

const environmentOptions = computed(() => [
  { value: '', label: 'None' },
  ...environments.value.map((env) => ({ value: env.uuid, label: env.name })),
]);

const ownershipDescription = computed(
  () => OWNERSHIP_OPTIONS.find((option) => option.value === form.ownership_type)?.description || '',
);

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true },
);

watch(
  () => props.error,
  (message) => {
    if (message) toast.error(message, 'Validation Failed');
  },
);

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true },
);

watch(
  () => form.customer_type,
  () => {
    localErrors.value = {};
  },
);

watch(
  () => form.industry_id,
  () => {
    if (!subIndustryOptions.value.some((option) => option.value === form.sub_industry_id)) {
      form.sub_industry_id = '';
    }
    if (!selectedIndustry.value?.is_other) {
      form.industry_other = '';
    }
  },
);

watch(
  () => form.application_id,
  (applicationId) => loadEnvironments(applicationId),
);

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

onMounted(async () => {
  await Promise.all([loadCompanies(), loadIndustries(), loadApplications(), loadLocations()]);
});

async function loadCompanies() {
  if (props.hideCompany) return;
  try {
    const { data } = await companyService.list({
      per_page: 100,
      status: 'active',
      sort_by: 'company_name',
      sort_dir: 'asc',
    });
    const items = data.data?.companies?.items ?? [];
    const currentId = props.initial?.company?.uuid || props.initial?.company_id;
    if (currentId && !items.some((company) => company.uuid === currentId)) {
      items.push({
        uuid: currentId,
        company_name: props.initial?.company?.company_name || props.initial?.company_name || 'Assigned company',
        status: 'inactive',
      });
    }
    companies.value = items;
  } catch {
    companies.value = [];
  }
}

async function loadIndustries() {
  try {
    const { data } = await customerService.industries();
    industries.value = data.data?.industries ?? [];
  } catch {
    industries.value = [];
  }
}

async function loadApplications() {
  if (props.initial?.uuid) return;
  try {
    const params = { per_page: 100, sort_by: 'name', sort_dir: 'asc' };
    if (form.company_id) params.company = form.company_id;
    const { data } = await applicationService.list(params);
    applications.value = data.data?.applications?.items ?? [];
  } catch {
    applications.value = [];
  }
}

async function loadEnvironments(applicationId) {
  if (!applicationId) {
    environments.value = [];
    form.application_environment_id = '';
    return;
  }
  try {
    const { data } = await environmentService.list(applicationId, { per_page: 100 });
    environments.value = data.data?.environments?.items ?? data.data?.environments ?? [];
  } catch {
    environments.value = [];
  }
}

function onCompanyChange() {
  form.location_id = '';
  form.application_id = '';
  loadApplications();
  loadLocations();
}

async function loadLocations() {
  if (!form.company_id) {
    locationItems.value = [];
    form.location_id = '';
    return;
  }

  try {
    const { data } = await companyService.listLocations({
      company: form.company_id,
      status: 'active',
      per_page: 100,
      page: 1,
    });
    const items = data.data?.locations?.items ?? [];
    const currentId = form.location_id;
    const assigned = props.initial?.location;

    if (currentId && assigned?.uuid === currentId && !items.some((item) => item.uuid === currentId)) {
      items.push({
        uuid: assigned.uuid,
        branch_name: assigned.branch_name || 'Assigned location',
        status: assigned.status || 'inactive',
      });
    }

    locationItems.value = items;

    if (currentId && !items.some((item) => item.uuid === currentId)) {
      form.location_id = '';
    }
  } catch {
    locationItems.value = [];
  }
}

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    location_id: value.location?.uuid || '',
    customer_type: value.customer_type || 'individual',
    reference: value.reference || '',
    first_name: value.first_name || '',
    last_name: value.last_name || '',
    company_name: value.company_name || '',
    legal_name: value.legal_name || '',
    registration_number: value.registration_number || '',
    primary_contact_name: value.primary_contact_name || '',
    primary_contact_title: value.primary_contact_title || '',
    primary_contact_email: value.primary_contact_email || '',
    primary_contact_phone: value.primary_contact_phone || '',
    email: value.email || '',
    phone: value.phone || '',
    website: value.website || '',
    industry_id: value.industry_master?.uuid || '',
    sub_industry_id: value.sub_industry?.uuid || '',
    industry_other: value.industry_other || '',
    language: value.language || 'en',
    legal_basis: value.legal_basis || '',
    processing_purpose: value.processing_purpose || '',
    retention_until: value.retention_until || '',
    status: value.status || 'active',
    notes: value.notes || '',
    application_id: '',
    application_environment_id: '',
    ownership_type: 'customer_owned',
  };
}

function fieldClass(field) {
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

function isHttpUrl(value) {
  try {
    const url = new URL(value);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch {
    return false;
  }
}

function validate() {
  const next = {};

  if (!props.hideCompany && !String(form.company_id || '').trim()) {
    next.company_id = ['Please select an owning company.'];
  }

  if (!String(form.customer_type || '').trim()) {
    next.customer_type = ['The customer type field is required.'];
  }

  if (isIndividual.value) {
    if (!String(form.first_name || '').trim()) next.first_name = ['First name is required for individual customers.'];
    if (!String(form.last_name || '').trim()) next.last_name = ['Last name is required for individual customers.'];
  } else if (!String(form.company_name || '').trim()) {
    next.company_name = ['Organization name is required for organization customers.'];
  }

  if (!String(form.legal_basis || '').trim()) {
    next.legal_basis = ['Legal basis is required.'];
  }

  if (!String(form.processing_purpose || '').trim()) {
    next.processing_purpose = ['Processing purpose is required.'];
  }

  if (!String(form.email || '').trim()) {
    next.email = ['The email field is required.'];
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    next.email = ['The email must be a valid email address.'];
  }

  if (form.primary_contact_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.primary_contact_email)) {
    next.primary_contact_email = ['Primary contact email must be a valid email address.'];
  }

  if (form.phone && !isValidE164(form.phone)) next.phone = [PHONE_INVALID_MESSAGE];
  if (form.primary_contact_phone && !isValidE164(form.primary_contact_phone)) {
    next.primary_contact_phone = [PHONE_INVALID_MESSAGE];
  }

  if (form.website && !isHttpUrl(form.website)) {
    next.website = ['The website must be a valid http or https URL.'];
  }

  if (selectedIndustry.value?.is_other && !String(form.industry_other || '').trim()) {
    next.industry_other = ['Describe the industry when Other is selected.'];
  }

  localErrors.value = next;
  return Object.keys(next).length === 0;
}

function onSubmit() {
  if (!validate()) {
    toast.error('Please fix the highlighted fields.', 'Validation Failed');
    return;
  }

  localErrors.value = {};
  const payload = {
    company_id: form.company_id,
    location_id: form.location_id || null,
    customer_type: form.customer_type,
    reference: form.reference || null,
    email: form.email,
    phone: form.phone || null,
    website: form.website || null,
    industry_id: form.industry_id || null,
    sub_industry_id: form.sub_industry_id || null,
    industry_other: form.industry_other || null,
    language: form.language || 'en',
    legal_basis: form.legal_basis || null,
    processing_purpose: form.processing_purpose || null,
    retention_until: form.retention_until || null,
    status: form.status,
    notes: form.notes || null,
  };

  if (isIndividual.value) {
    payload.first_name = form.first_name;
    payload.last_name = form.last_name;
    payload.company_name = null;
  } else {
    payload.company_name = form.company_name;
    payload.legal_name = form.legal_name || null;
    payload.registration_number = form.registration_number || null;
    payload.primary_contact_name = form.primary_contact_name || null;
    payload.primary_contact_title = form.primary_contact_title || null;
    payload.primary_contact_email = form.primary_contact_email || null;
    payload.primary_contact_phone = form.primary_contact_phone || null;
    payload.first_name = null;
    payload.last_name = null;
  }

  if (!props.initial?.uuid && form.application_id) {
    payload.application = {
      application_id: form.application_id,
      application_environment_id: form.application_environment_id || null,
      ownership_type: form.ownership_type,
    };
  }

  emit('submit', payload);
}
</script>

<style scoped>
.field {
  height: 3rem;
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background: #fff;
  padding: 0 0.875rem;
  font-size: 0.875rem;
  color: #0f172a;
  outline: none;
  box-shadow: none;
}
.area {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background: #fff;
  padding: 0.75rem 0.875rem;
  font-size: 0.875rem;
  color: #0f172a;
  outline: none;
  box-shadow: none;
}
.field:focus,
.area:focus {
  border-color: var(--color-brand-500, #f97316);
}
</style>
