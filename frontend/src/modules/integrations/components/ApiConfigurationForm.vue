<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Base URL</label>
        <input v-model="form.base_url" type="url" class="input" placeholder="https://" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Health check path</label>
        <input v-model="form.health_check_path" type="text" class="input" placeholder="/health" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timeout (seconds)</label>
        <input v-model.number="form.timeout" type="number" min="1" max="300" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Retry attempts</label>
        <input v-model.number="form.retry_attempts" type="number" min="0" max="10" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Rate limit / minute</label>
        <input v-model.number="form.rate_limit_per_minute" type="number" min="1" max="10000" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Authentication type</label>
        <select v-model="form.authentication_type" class="input">
          <option value="api_key">API Key</option>
          <option value="bearer_token">Bearer Token</option>
          <option value="basic_auth">Basic Auth</option>
          <option value="jwt">JWT</option>
          <option value="oauth2">OAuth2</option>
        </select>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Default headers (JSON object)</label>
      <textarea v-model="headersText" rows="4" class="input font-mono text-xs" placeholder='{"Accept":"application/json"}' />
      <p v-if="headersError" class="mt-1 text-xs text-rose-600">{{ headersError }}</p>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Default query (JSON object)</label>
      <textarea v-model="queryText" rows="3" class="input font-mono text-xs" placeholder='{"lang":"en"}' />
      <p v-if="queryError" class="mt-1 text-xs text-rose-600">{{ queryError }}</p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800">Credentials (encrypted at rest)</h3>
        <p class="text-xs text-slate-500">
          {{ initial.has_credentials ? `Configured: ${(initial.credential_keys || []).join(', ') || 'yes'}` : 'Not configured' }}
        </p>
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <template v-if="form.authentication_type === 'api_key'">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">API key</label>
            <input v-model="form.credentials.api_key" type="password" class="input" autocomplete="off" placeholder="Leave blank to keep existing" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Header name</label>
            <input v-model="form.credentials.api_key_header" type="text" class="input" placeholder="X-API-Key" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Location</label>
            <select v-model="form.credentials.api_key_location" class="input">
              <option value="header">Header</option>
              <option value="query">Query</option>
            </select>
          </div>
        </template>
        <template v-else-if="form.authentication_type === 'bearer_token'">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Bearer token</label>
            <input v-model="form.credentials.bearer_token" type="password" class="input" autocomplete="off" placeholder="Leave blank to keep existing" />
          </div>
        </template>
        <template v-else-if="form.authentication_type === 'basic_auth'">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
            <input v-model="form.credentials.username" type="text" class="input" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input v-model="form.credentials.password" type="password" class="input" autocomplete="off" placeholder="Leave blank to keep existing" />
          </div>
        </template>
        <template v-else-if="form.authentication_type === 'jwt'">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">JWT token</label>
            <input v-model="form.credentials.jwt_token" type="password" class="input" autocomplete="off" placeholder="Leave blank to keep existing" />
          </div>
        </template>
        <template v-else>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">OAuth2 access token</label>
            <input v-model="form.credentials.oauth_access_token" type="password" class="input" autocomplete="off" placeholder="Leave blank to keep existing" />
          </div>
        </template>
      </div>
      <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.clear_credentials" type="checkbox" class="rounded border-slate-300" />
        Clear all stored credentials
      </label>
    </div>

    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">Cancel</button>
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Saving...' : 'Save configuration' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits(['submit', 'cancel']);

const headersText = ref('{}');
const queryText = ref('{}');
const headersError = ref('');
const queryError = ref('');
const form = reactive(createForm(props.initial));

watch(() => props.initial, (value) => {
  Object.assign(form, createForm(value));
  headersText.value = JSON.stringify(value.default_headers || {}, null, 2);
  queryText.value = JSON.stringify(value.default_query || {}, null, 2);
}, { deep: true, immediate: true });

function createForm(value = {}) {
  return {
    base_url: value.base_url || '',
    health_check_path: value.health_check_path || '',
    timeout: value.timeout ?? 30,
    retry_attempts: value.retry_attempts ?? 3,
    rate_limit_per_minute: value.rate_limit_per_minute ?? null,
    authentication_type: value.authentication_type || 'api_key',
    clear_credentials: false,
    credentials: {
      api_key: '',
      api_key_header: 'X-API-Key',
      api_key_location: 'header',
      bearer_token: '',
      username: '',
      password: '',
      jwt_token: '',
      oauth_access_token: '',
    },
  };
}

function onSubmit() {
  headersError.value = '';
  queryError.value = '';
  let default_headers = {};
  let default_query = {};
  try {
    default_headers = headersText.value.trim() ? JSON.parse(headersText.value) : {};
  } catch {
    headersError.value = 'Invalid JSON for headers';
    return;
  }
  try {
    default_query = queryText.value.trim() ? JSON.parse(queryText.value) : {};
  } catch {
    queryError.value = 'Invalid JSON for query';
    return;
  }

  const credentials = Object.fromEntries(
    Object.entries(form.credentials).filter(([, value]) => value !== '' && value != null),
  );

  emit('submit', {
    base_url: form.base_url || null,
    health_check_path: form.health_check_path || null,
    timeout: form.timeout,
    retry_attempts: form.retry_attempts,
    rate_limit_per_minute: form.rate_limit_per_minute || null,
    authentication_type: form.authentication_type,
    default_headers,
    default_query,
    credentials,
    clear_credentials: form.clear_credentials,
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
