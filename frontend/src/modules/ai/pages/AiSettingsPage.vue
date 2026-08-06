<template>
  <div>
    <PageHeader
      title="AI Settings"
      description="Configure AI providers through the driver registry. No vendor is hardcoded in application services."
    />
    <AiSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
      <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Quick add</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="preset in quickPresets"
          :key="preset.driver"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
          :class="form.driver === preset.driver && !editingUuid
            ? 'border-brand-300 bg-brand-50 text-brand-700'
            : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
          @click="applyQuickAdd(preset.driver)"
        >
          {{ preset.label }}
        </button>
      </div>
    </div>

    <div class="mb-6 grid gap-4 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-1">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">{{ editingUuid ? 'Edit provider' : 'Add provider' }}</h2>
        <form class="space-y-3" @submit.prevent="saveProvider">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
            <input
              v-model="form.name"
              required
              placeholder="e.g. Google Gemini"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Driver</label>
            <select
              v-model="form.driver"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              @change="onDriverChange"
            >
              <option v-for="driver in store.catalog.drivers || []" :key="driver.value" :value="driver.value">
                {{ driver.label }}{{ driver.registered ? '' : ' (unregistered)' }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Default model</label>
            <input
              v-model="form.default_model"
              :placeholder="activePreset?.default_model || 'model-id'"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Embedding model</label>
            <input
              v-model="form.embedding_model"
              :placeholder="activePreset?.embedding_model || 'optional'"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Base URL</label>
            <input
              v-model="form.base_url"
              type="text"
              :placeholder="activePreset?.base_url || 'Leave blank for driver default'"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
            <p v-if="activePreset?.hint" class="mt-1 text-xs text-slate-500">{{ activePreset.hint }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">API key</label>
            <input
              v-model="form.api_key"
              type="password"
              autocomplete="off"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              :placeholder="editingUuid ? 'Leave blank to keep existing' : (form.driver === 'null' ? 'Not required for Null' : 'Required for live providers')"
            />
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_default" type="checkbox" class="rounded border-slate-300" />
            Default provider
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300" />
            Enabled
          </label>
          <div class="flex gap-2">
            <button
              type="submit"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              {{ store.saving ? 'Saving…' : (editingUuid ? 'Update' : 'Create') }}
            </button>
            <button
              v-if="editingUuid"
              type="button"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700"
              @click="resetForm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Providers</h2>
          <button class="text-xs font-medium text-brand-700 hover:underline" @click="store.fetchProviders()">Refresh</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
              <tr>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">Driver</th>
                <th class="px-3 py-2">Health</th>
                <th class="px-3 py-2">Flags</th>
                <th class="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!store.providers.length">
                <td colspan="5" class="px-3 py-8 text-center text-slate-500">No providers configured.</td>
              </tr>
              <tr v-for="provider in store.providers" :key="provider.uuid" class="border-b border-slate-100">
                <td class="px-3 py-3">
                  <p class="font-medium text-slate-900">{{ provider.name }}</p>
                  <p class="text-xs text-slate-500">{{ provider.default_model || 'No model' }}</p>
                </td>
                <td class="px-3 py-3">{{ provider.driver_label || provider.driver }}</td>
                <td class="px-3 py-3">
                  <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="healthClass(provider.health_status)"
                  >
                    {{ provider.health_status || 'unknown' }}
                  </span>
                </td>
                <td class="px-3 py-3 text-xs text-slate-600">
                  <span v-if="provider.is_default">default · </span>
                  <span>{{ provider.is_enabled ? 'enabled' : 'disabled' }}</span>
                </td>
                <td class="px-3 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <button class="text-xs font-medium text-brand-700" @click="editProvider(provider)">Edit</button>
                    <button class="text-xs font-medium text-slate-700" @click="testProvider(provider)">Test</button>
                    <button class="text-xs font-medium text-rose-600" @click="removeProvider(provider)">Delete</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const editingUuid = ref(null);

const driverPresets = {
  openai: {
    name: 'OpenAI',
    default_model: 'gpt-4o-mini',
    embedding_model: 'text-embedding-3-small',
    base_url: 'https://api.openai.com/v1',
    hint: 'Paste your OpenAI API key, then Create → Test.',
  },
  azure_openai: {
    name: 'Azure OpenAI',
    default_model: 'gpt-4o-mini',
    embedding_model: 'text-embedding-3-small',
    base_url: '',
    hint: 'Set your Azure resource endpoint as Base URL (required).',
  },
  gemini: {
    name: 'Google Gemini',
    default_model: 'gemini-2.0-flash',
    embedding_model: 'text-embedding-004',
    base_url: 'https://generativelanguage.googleapis.com/v1beta',
    hint: 'Paste your Google AI Studio API key, then Create → set Default → Test.',
  },
  claude: {
    name: 'Anthropic Claude',
    default_model: 'claude-3-5-sonnet-latest',
    embedding_model: '',
    base_url: 'https://api.anthropic.com/v1',
    hint: 'Paste your Anthropic API key, then Create → Test.',
  },
  null: {
    name: 'Local Null Provider',
    default_model: 'null-model',
    embedding_model: 'null-embed',
    base_url: '',
    hint: 'Local stub for development — no API key needed.',
  },
  custom: {
    name: 'Custom AI',
    default_model: '',
    embedding_model: '',
    base_url: '',
    hint: 'OpenAI-compatible endpoint. Base URL is required.',
  },
};

const quickPresets = [
  { driver: 'gemini', label: 'Google Gemini' },
  { driver: 'openai', label: 'OpenAI' },
  { driver: 'claude', label: 'Anthropic Claude' },
  { driver: 'azure_openai', label: 'Azure OpenAI' },
  { driver: 'custom', label: 'Custom AI' },
  { driver: 'null', label: 'Null Stub' },
];

const form = reactive({
  name: '',
  driver: 'gemini',
  default_model: '',
  embedding_model: '',
  base_url: '',
  api_key: '',
  is_default: false,
  is_enabled: true,
});

const activePreset = computed(() => driverPresets[form.driver] || null);

function applyPresetFields(driver, { fillName = true } = {}) {
  const preset = driverPresets[driver];
  if (!preset) return;
  form.driver = driver;
  if (fillName || !form.name) {
    form.name = preset.name;
  }
  form.default_model = preset.default_model;
  form.embedding_model = preset.embedding_model;
  form.base_url = preset.base_url;
}

function applyQuickAdd(driver) {
  editingUuid.value = null;
  form.api_key = '';
  form.is_default = false;
  form.is_enabled = true;
  applyPresetFields(driver, { fillName: true });
}

function onDriverChange() {
  if (editingUuid.value) return;
  applyPresetFields(form.driver, { fillName: true });
}

function resetForm() {
  editingUuid.value = null;
  form.api_key = '';
  form.is_default = false;
  form.is_enabled = true;
  applyPresetFields('gemini', { fillName: true });
}

function editProvider(provider) {
  editingUuid.value = provider.uuid;
  form.name = provider.name;
  form.driver = provider.driver;
  form.default_model = provider.default_model || '';
  form.embedding_model = provider.embedding_model || '';
  form.base_url = provider.base_url || '';
  form.api_key = '';
  form.is_default = !!provider.is_default;
  form.is_enabled = !!provider.is_enabled;
}

function healthClass(status) {
  if (status === 'healthy') return 'bg-emerald-50 text-emerald-700';
  if (status === 'unhealthy') return 'bg-rose-50 text-rose-700';
  return 'bg-slate-100 text-slate-600';
}

async function saveProvider() {
  store.error = null;
  store.successMessage = null;

  if (!editingUuid.value && form.driver !== 'null' && !form.api_key) {
    store.error = 'API key is required when adding a live AI provider. Use Null Stub for local testing without keys.';
    return;
  }

  if (!editingUuid.value && form.driver === 'azure_openai' && !form.base_url) {
    store.error = 'Azure OpenAI requires a Base URL (your Azure resource endpoint).';
    return;
  }

  if (!editingUuid.value && form.driver === 'custom' && !form.base_url) {
    store.error = 'Custom AI requires a Base URL.';
    return;
  }

  const payload = {
    name: form.name,
    driver: form.driver,
    default_model: form.default_model || null,
    embedding_model: form.embedding_model || null,
    base_url: form.base_url || null,
    is_default: form.is_default,
    is_enabled: form.is_enabled,
  };
  if (form.api_key) {
    payload.credentials = { api_key: form.api_key };
  }

  try {
    if (editingUuid.value) {
      await store.updateProvider(editingUuid.value, payload);
    } else {
      await store.createProvider(payload);
    }
    resetForm();
    await store.fetchProviders();
  } catch {
    // store.error already set
  }
}

async function testProvider(provider) {
  try {
    const result = await store.testProvider(provider.uuid);
    store.successMessage = result?.message || (result?.ok ? 'Healthy' : 'Unhealthy');
    if (!result?.ok) {
      store.error = result?.message || 'Connection test failed.';
      store.successMessage = null;
    }
    await store.fetchProviders();
  } catch (error) {
    store.error = error?.response?.data?.message || error?.message || 'Connection test failed.';
  }
}

async function removeProvider(provider) {
  if (!window.confirm(`Delete provider “${provider.name}”?`)) return;
  try {
    await store.deleteProvider(provider.uuid);
    await store.fetchProviders();
  } catch {
    // store.error already set
  }
}

onMounted(async () => {
  await store.fetchCatalog();
  await store.fetchProviders();
  if (!editingUuid.value) {
    applyPresetFields('gemini', { fillName: true });
  }
});
</script>
