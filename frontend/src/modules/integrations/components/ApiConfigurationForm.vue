<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div>
      <h3 class="text-base font-semibold text-slate-900">Endpoint settings</h3>
      <p class="mt-1 text-sm text-slate-500">
        Base connection details, timeouts, and authentication method.
      </p>

      <div class="mt-5 grid gap-x-10 gap-y-5 md:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Base URL</label>
          <input
            v-model="form.base_url"
            type="url"
            placeholder="https://"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Health check path</label>
          <input
            v-model="form.health_check_path"
            type="text"
            placeholder="/health"
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
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Retry attempts</label>
          <input
            v-model.number="form.retry_attempts"
            type="number"
            min="0"
            max="10"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Rate limit / minute</label>
          <input
            v-model.number="form.rate_limit_per_minute"
            type="number"
            min="1"
            max="10000"
            placeholder="Optional"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Authentication type</label>
          <SelectBox v-model="form.authentication_type" size="lg" :options="authOptions" />
        </div>
      </div>
    </div>

    <div>
      <h3 class="text-base font-semibold text-slate-900">Defaults</h3>
      <p class="mt-1 text-sm text-slate-500">
        JSON objects applied to every request from this integration.
      </p>

      <div class="mt-5 space-y-5">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700"
            >Default headers (JSON object)</label
          >
          <textarea
            v-model="headersText"
            rows="4"
            placeholder='{"Accept":"application/json"}'
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-xs text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
          <p v-if="headersError" class="mt-1 text-xs text-rose-600">{{ headersError }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700"
            >Default query (JSON object)</label
          >
          <textarea
            v-model="queryText"
            rows="3"
            placeholder='{"lang":"en"}'
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-xs text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
          <p v-if="queryError" class="mt-1 text-xs text-rose-600">{{ queryError }}</p>
        </div>
      </div>
    </div>

    <div class="rounded-[12px] bg-zinc-50 p-5 sm:p-6">
      <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Credentials</h3>
          <p class="mt-0.5 text-sm text-slate-500">Encrypted at rest. Leave blank to keep existing.</p>
        </div>
        <p class="text-xs font-medium text-slate-500">
          {{
            initial.has_credentials
              ? `Configured: ${(initial.credential_keys || []).join(', ') || 'yes'}`
              : 'Not configured'
          }}
        </p>
      </div>

      <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
        <template v-if="form.authentication_type === 'api_key'">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">API key</label>
            <input
              v-model="form.credentials.api_key"
              type="password"
              autocomplete="off"
              placeholder="Leave blank to keep existing"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Header name</label>
            <input
              v-model="form.credentials.api_key_header"
              type="text"
              placeholder="X-API-Key"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
            <SelectBox
              v-model="form.credentials.api_key_location"
              size="lg"
              :options="locationOptions"
            />
          </div>
        </template>

        <template v-else-if="form.authentication_type === 'bearer_token'">
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Bearer token</label>
            <input
              v-model="form.credentials.bearer_token"
              type="password"
              autocomplete="off"
              placeholder="Leave blank to keep existing"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
        </template>

        <template v-else-if="form.authentication_type === 'basic_auth'">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
            <input
              v-model="form.credentials.username"
              type="text"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
            <input
              v-model="form.credentials.password"
              type="password"
              autocomplete="off"
              placeholder="Leave blank to keep existing"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
        </template>

        <template v-else-if="form.authentication_type === 'jwt'">
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">JWT token</label>
            <input
              v-model="form.credentials.jwt_token"
              type="password"
              autocomplete="off"
              placeholder="Leave blank to keep existing"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
        </template>

        <template v-else>
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-slate-700"
              >OAuth2 access token</label
            >
            <input
              v-model="form.credentials.oauth_access_token"
              type="password"
              autocomplete="off"
              placeholder="Leave blank to keep existing"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
        </template>
      </div>

      <label class="mt-5 flex items-center gap-2.5 text-sm text-slate-700">
        <input
          v-model="form.clear_credentials"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/20"
        />
        Clear all stored credentials
      </label>
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
        {{ loading ? 'Saving...' : 'Save configuration' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);

const authOptions = [
  { value: 'api_key', label: 'API Key' },
  { value: 'bearer_token', label: 'Bearer Token' },
  { value: 'basic_auth', label: 'Basic Auth' },
  { value: 'jwt', label: 'JWT' },
  { value: 'oauth2', label: 'OAuth2' },
];

const locationOptions = [
  { value: 'header', label: 'Header' },
  { value: 'query', label: 'Query' },
];

const headersText = ref('{}');
const queryText = ref('{}');
const headersError = ref('');
const queryError = ref('');
const form = reactive(createForm(props.initial));

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
    headersText.value = JSON.stringify(normalizeJsonObject(value.default_headers), null, 2);
    queryText.value = JSON.stringify(normalizeJsonObject(value.default_query), null, 2);
  },
  { deep: true, immediate: true },
);

function normalizeJsonObject(value) {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return value;
  }
  return {};
}

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
    if (Array.isArray(default_headers) || typeof default_headers !== 'object' || default_headers === null) {
      headersError.value = 'Headers must be a JSON object';
      return;
    }
  } catch {
    headersError.value = 'Invalid JSON for headers';
    return;
  }

  try {
    default_query = queryText.value.trim() ? JSON.parse(queryText.value) : {};
    if (Array.isArray(default_query) || typeof default_query !== 'object' || default_query === null) {
      queryError.value = 'Query must be a JSON object';
      return;
    }
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
