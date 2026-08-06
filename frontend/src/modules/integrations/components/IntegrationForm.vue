<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required :disabled="Boolean(initial.uuid)">
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
        <p v-if="errors.company_id" class="mt-1 text-xs text-rose-600">{{ errors.company_id[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input v-model="form.name" type="text" class="input" required />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
        <input v-model="form.slug" type="text" class="input" placeholder="auto-generated if empty" />
        <p v-if="errors.slug" class="mt-1 text-xs text-rose-600">{{ errors.slug[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
        <select v-model="form.type" class="input" required>
          <option value="rest_api">REST API</option>
          <option value="graphql">GraphQL</option>
          <option value="webhook">Webhook</option>
          <option value="sdk">SDK</option>
          <option value="ftp">FTP</option>
          <option value="database">Database</option>
        </select>
        <p v-if="errors.type" class="mt-1 text-xs text-rose-600">{{ errors.type[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Authentication</label>
        <select v-model="form.authentication_type" class="input" required>
          <option value="api_key">API Key</option>
          <option value="bearer_token">Bearer Token</option>
          <option value="basic_auth">Basic Auth</option>
          <option value="jwt">JWT</option>
          <option value="oauth2">OAuth2</option>
        </select>
        <p v-if="errors.authentication_type" class="mt-1 text-xs text-rose-600">{{ errors.authentication_type[0] }}</p>
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
        <input v-model="form.base_url" type="url" class="input" placeholder="https://" />
        <p v-if="errors.base_url" class="mt-1 text-xs text-rose-600">{{ errors.base_url[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">API version</label>
        <input v-model="form.api_version" type="text" class="input" placeholder="v1" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timeout (seconds)</label>
        <input v-model.number="form.timeout" type="number" min="1" max="300" class="input" />
        <p v-if="errors.timeout" class="mt-1 text-xs text-rose-600">{{ errors.timeout[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Retry attempts</label>
        <input v-model.number="form.retry_attempts" type="number" min="0" max="10" class="input" />
        <p v-if="errors.retry_attempts" class="mt-1 text-xs text-rose-600">{{ errors.retry_attempts[0] }}</p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="3" class="input" />
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
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideCompany: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel']);

const companies = ref([]);
const form = reactive(createForm(props.initial));
watch(() => props.initial, (value) => Object.assign(form, createForm(value)), { deep: true });

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
