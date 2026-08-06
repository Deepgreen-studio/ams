<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Method</label>
        <select v-model="form.method" class="input">
          <option>GET</option>
          <option>POST</option>
          <option>PUT</option>
          <option>PATCH</option>
          <option>DELETE</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Path</label>
        <input v-model="form.path" type="text" class="input" placeholder="/v1/resources" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timeout override</label>
        <input v-model.number="form.timeout" type="number" min="1" max="300" class="input" placeholder="Use integration default" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Retry override</label>
        <input v-model.number="form.retry_attempts" type="number" min="0" max="10" class="input" placeholder="Use integration default" />
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Headers (JSON)</label>
      <textarea v-model="headersText" rows="3" class="input font-mono text-xs" placeholder="{}" />
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Query parameters (JSON)</label>
      <textarea v-model="queryText" rows="3" class="input font-mono text-xs" placeholder="{}" />
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">JSON body</label>
      <textarea v-model="bodyText" rows="6" class="input font-mono text-xs" placeholder='{"key":"value"}' />
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">File upload (optional)</label>
      <input type="file" class="block w-full text-sm text-slate-600" @change="onFile" />
    </div>

    <div class="flex flex-wrap gap-4">
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.apply_auth" type="checkbox" class="rounded border-slate-300" />
        Apply authentication
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.as_download" type="checkbox" class="rounded border-slate-300" />
        Treat response as download
      </label>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Sending...' : 'Send request' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});
const emit = defineEmits(['submit']);

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
