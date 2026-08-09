<template>
  <form class="space-y-4" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select
          v-model="form.company_id"
          class="input"
          :class="fieldClass('company_id')"
          :disabled="Boolean(initial.uuid)"
        >
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="form.name"
          type="text"
          class="input"
          :class="fieldClass('name')"
        />
        <p v-if="displayErrors.name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.name[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
        <input
          v-model="form.slug"
          type="text"
          class="input"
          placeholder="auto-generated if empty"
          :class="fieldClass('slug')"
        />
        <p v-if="displayErrors.slug" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.slug[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
        <select
          v-model="form.type"
          class="input"
          :class="fieldClass('type')"
        >
          <option value="rest_api">REST API</option>
          <option value="graphql">GraphQL</option>
          <option value="webhook">Webhook</option>
          <option value="sdk">SDK</option>
          <option value="ftp">FTP</option>
          <option value="database">Database</option>
        </select>
        <p v-if="displayErrors.type" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.type[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Authentication</label>
        <select
          v-model="form.authentication_type"
          class="input"
          :class="fieldClass('authentication_type')"
        >
          <option value="api_key">API Key</option>
          <option value="bearer_token">Bearer Token</option>
          <option value="basic_auth">Basic Auth</option>
          <option value="jwt">JWT</option>
          <option value="oauth2">OAuth2</option>
        </select>
        <p v-if="displayErrors.authentication_type" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.authentication_type[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="error">Error</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Base URL</label>
        <input
          v-model="form.base_url"
          type="url"
          class="input"
          placeholder="https://"
          :class="fieldClass('base_url')"
        />
        <p v-if="displayErrors.base_url" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.base_url[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">API version</label>
        <input v-model="form.api_version" type="text" class="input" placeholder="v1" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timeout (seconds)</label>
        <input
          v-model.number="form.timeout"
          type="number"
          min="1"
          max="300"
          class="input"
          :class="fieldClass('timeout')"
        />
        <p v-if="displayErrors.timeout" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.timeout[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Retry attempts</label>
        <input
          v-model.number="form.retry_attempts"
          type="number"
          min="0"
          max="10"
          class="input"
          :class="fieldClass('retry_attempts')"
        />
        <p v-if="displayErrors.retry_attempts" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.retry_attempts[0] }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="3" class="input" />
      </div>
    </div>
    <div class="flex justify-end gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        :disabled="loading"
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
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

watch(() => props.initial, (value) => Object.assign(form, createForm(value)), { deep: true });

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true }
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
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
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

  if (form.retry_attempts !== null && form.retry_attempts !== undefined && form.retry_attempts !== '') {
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
