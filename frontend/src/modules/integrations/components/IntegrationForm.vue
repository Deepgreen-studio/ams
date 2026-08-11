<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companyOptions"
          :disabled="Boolean(initial.uuid)"
          :error="Boolean(displayErrors.company_id)"
        />
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="EasyCare API"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('name')"
        />
        <p v-if="displayErrors.name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.name[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Slug</label>
        <input
          v-model="form.slug"
          type="text"
          placeholder="auto-generated if empty"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('slug')"
        />
        <p v-if="displayErrors.slug" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.slug[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
        <SelectBox
          v-model="form.type"
          size="lg"
          :options="typeOptions"
          :error="Boolean(displayErrors.type)"
        />
        <p v-if="displayErrors.type" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.type[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Authentication</label>
        <SelectBox
          v-model="form.authentication_type"
          size="lg"
          :options="authOptions"
          :error="Boolean(displayErrors.authentication_type)"
        />
        <p v-if="displayErrors.authentication_type" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.authentication_type[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Base URL</label>
        <input
          v-model="form.base_url"
          type="url"
          placeholder="https://"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('base_url')"
        />
        <p v-if="displayErrors.base_url" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.base_url[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">API version</label>
        <input
          v-model="form.api_version"
          type="text"
          placeholder="v1"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Timeout (seconds)</label>
        <input
          v-model.number="form.timeout"
          type="number"
          min="1"
          max="300"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('timeout')"
        />
        <p v-if="displayErrors.timeout" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.timeout[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Retry attempts</label>
        <input
          v-model.number="form.retry_attempts"
          type="number"
          min="0"
          max="10"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('retry_attempts')"
        />
        <p v-if="displayErrors.retry_attempts" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.retry_attempts[0] }}
        </p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Short summary of this integration"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
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
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';

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
const localErrors = ref({});
const form = reactive(createForm(props.initial));

const typeOptions = [
  { value: 'rest_api', label: 'REST API' },
  { value: 'graphql', label: 'GraphQL' },
  { value: 'webhook', label: 'Webhook' },
  { value: 'sdk', label: 'SDK' },
  { value: 'ftp', label: 'FTP' },
  { value: 'database', label: 'Database' },
];

const authOptions = [
  { value: 'api_key', label: 'API Key' },
  { value: 'bearer_token', label: 'Bearer Token' },
  { value: 'basic_auth', label: 'Basic Auth' },
  { value: 'jwt', label: 'JWT' },
  { value: 'oauth2', label: 'OAuth2' },
];

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'error', label: 'Error' },
];

const companyOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true },
);

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  },
);

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true },
);

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

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
    slug: value.slug || '',
    description: value.description || '',
    type: value.type || 'rest_api',
    authentication_type: value.authentication_type || 'api_key',
    status: value.status || 'draft',
    base_url: value.base_url || '',
    api_version: value.api_version || '',
    timeout: value.timeout ?? 30,
    retry_attempts: value.retry_attempts ?? 3,
  };
}

function fieldClass(field) {
  return displayErrors.value?.[field]
    ? 'border-rose-400 focus:border-rose-500'
    : '';
}

function isValidUrl(value) {
  try {
    void new URL(value);
    return true;
  } catch {
    return false;
  }
}

function validate() {
  const next = {};

  if (!props.hideCompany && !String(form.company_id || '').trim()) {
    next.company_id = ['Please select a company.'];
  }

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (!String(form.type || '').trim()) {
    next.type = ['The type field is required.'];
  }

  if (!String(form.authentication_type || '').trim()) {
    next.authentication_type = ['The authentication field is required.'];
  }

  if (form.base_url && !isValidUrl(form.base_url)) {
    next.base_url = ['The base URL must be a valid URL.'];
  }

  if (form.timeout !== null && form.timeout !== undefined && form.timeout !== '') {
    const timeout = Number(form.timeout);
    if (!Number.isInteger(timeout) || timeout < 1 || timeout > 300) {
      next.timeout = ['Timeout must be between 1 and 300 seconds.'];
    }
  }

  if (
    form.retry_attempts !== null &&
    form.retry_attempts !== undefined &&
    form.retry_attempts !== ''
  ) {
    const retries = Number(form.retry_attempts);
    if (!Number.isInteger(retries) || retries < 0 || retries > 10) {
      next.retry_attempts = ['Retry attempts must be between 0 and 10.'];
    }
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
  emit('submit', { ...form });
}
</script>
