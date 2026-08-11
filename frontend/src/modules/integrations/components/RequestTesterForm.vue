<template>
  <form class="space-y-6" novalidate @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div>
      <h3 class="text-base font-semibold text-slate-900">Request</h3>
      <p class="mt-1 text-sm text-slate-500">
        Send a request through the shared API Connection Engine.
      </p>

      <div class="mt-5 grid gap-x-6 gap-y-5 md:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Method</label>
          <SelectBox v-model="form.method" size="lg" :options="methodOptions" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Path</label>
          <input
            v-model="form.path"
            type="text"
            placeholder="/v1/resources"
            required
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Timeout override</label>
          <input
            v-model.number="form.timeout"
            type="number"
            min="1"
            max="300"
            placeholder="Use integration default"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Retry override</label>
          <input
            v-model.number="form.retry_attempts"
            type="number"
            min="0"
            max="10"
            placeholder="Use integration default"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
      </div>
    </div>

    <div class="space-y-5">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Headers (JSON)</label>
        <textarea
          v-model="headersText"
          rows="3"
          placeholder="{}"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-xs text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700"
          >Query parameters (JSON)</label
        >
        <textarea
          v-model="queryText"
          rows="3"
          placeholder="{}"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-xs text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">JSON body</label>
        <textarea
          v-model="bodyText"
          rows="6"
          placeholder='{"key":"value"}'
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-xs text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">File upload (optional)</label>
        <input
          type="file"
          class="block w-full rounded-xl border border-dashed border-slate-200 bg-zinc-50 px-3.5 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700"
          @change="onFile"
        />
      </div>
    </div>

    <div class="flex flex-wrap gap-5">
      <label class="flex items-center gap-2.5 text-sm text-slate-700">
        <input
          v-model="form.apply_auth"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/20"
        />
        Apply authentication
      </label>
      <label class="flex items-center gap-2.5 text-sm text-slate-700">
        <input
          v-model="form.as_download"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/20"
        />
        Treat response as download
      </label>
    </div>

    <div class="flex justify-end border-t border-slate-100 pt-6">
      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Sending...' : 'Send request' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit']);

const methodOptions = [
  { value: 'GET', label: 'GET' },
  { value: 'POST', label: 'POST' },
  { value: 'PUT', label: 'PUT' },
  { value: 'PATCH', label: 'PATCH' },
  { value: 'DELETE', label: 'DELETE' },
];

const headersText = ref('{}');
const queryText = ref('{}');
const bodyText = ref('');
const file = ref(null);
const form = reactive({
  method: 'GET',
  path: '/',
  timeout: null,
  retry_attempts: null,
  apply_auth: true,
  as_download: false,
});

function onFile(event) {
  file.value = event.target.files?.[0] || null;
}

function onSubmit() {
  let headers = {};
  let query = {};
  let body = null;
  try {
    headers = headersText.value.trim() ? JSON.parse(headersText.value) : {};
    query = queryText.value.trim() ? JSON.parse(queryText.value) : {};
    if (bodyText.value.trim()) {
      body = JSON.parse(bodyText.value);
    }
  } catch {
    emit('submit', null);
    return;
  }

  emit('submit', {
    payload: {
      method: form.method,
      path: form.path,
      headers,
      query,
      body,
      apply_auth: form.apply_auth,
      as_download: form.as_download,
      timeout: form.timeout || undefined,
      retry_attempts: form.retry_attempts ?? undefined,
    },
    file: file.value,
  });
}
</script>
