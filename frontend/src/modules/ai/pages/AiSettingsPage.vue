<template>
  <div>
    <AiSubnav />

    <section class="mb-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
      <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">Quick add</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="preset in quickPresets"
          :key="preset.driver"
          type="button"
          class="rounded-[12px] border px-3.5 py-2 text-sm font-medium transition"
          :class="form.driver === preset.driver && !editingUuid
            ? 'border-brand-300 bg-brand-50 text-brand-700'
            : 'border-zinc-200 text-slate-700 hover:bg-zinc-50'"
          @click="applyQuickAdd(preset.driver)"
        >
          {{ preset.label }}
        </button>
      </div>
    </section>

    <div class="mb-4 grid gap-4 lg:grid-cols-3">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-1">
        <h2 class="mb-4 text-base font-semibold text-slate-900">
          {{ editingUuid ? 'Edit provider' : 'Add provider' }}
        </h2>
        <form class="space-y-4" @submit.prevent="saveProvider">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
            <input
              v-model="form.name"
              required
              placeholder="e.g. Google Gemini"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Driver</label>
            <SelectBox
              v-model="form.driver"
              :options="driverOptions"
              placeholder="Select driver"
              @change="onDriverChange"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Default model</label>
            <input
              v-model="form.default_model"
              :placeholder="activePreset?.default_model || 'model-id'"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Embedding model</label>
            <input
              v-model="form.embedding_model"
              :placeholder="activePreset?.embedding_model || 'optional'"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Base URL</label>
            <input
              v-model="form.base_url"
              type="text"
              :placeholder="activePreset?.base_url || 'Leave blank for driver default'"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <p v-if="activePreset?.hint" class="mt-1.5 text-xs text-slate-500">{{ activePreset.hint }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">API key</label>
            <input
              v-model="form.api_key"
              type="password"
              autocomplete="off"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              :placeholder="editingUuid ? 'Leave blank to keep existing' : (form.driver === 'null' ? 'Not required for Null' : 'Required for live providers')"
            />
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_default" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
            Default provider
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
            Enabled
          </label>
          <div class="flex flex-wrap gap-2 pt-1">
            <button
              type="submit"
              class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              {{ store.saving ? 'Saving…' : (editingUuid ? 'Update' : 'Create') }}
            </button>
            <button
              v-if="editingUuid"
              type="button"
              class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetForm"
            >
              Cancel
            </button>
          </div>
        </form>
      </section>

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 lg:col-span-2">
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Providers</h2>
          <button
            type="button"
            class="text-xs font-medium text-brand-700 hover:underline"
            @click="store.fetchProviders()"
          >
            Refresh
          </button>
        </div>

        <div v-if="store.loading && !store.providers.length" class="space-y-3 px-6 py-6">
          <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>

        <EmptyState
          v-else-if="!store.providers.length"
          title="No providers configured"
          description="Add an AI provider using the form, or pick a quick-add preset."
          class="px-6 py-10"
        />

        <div v-else class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Name</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Driver</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Health</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Flags</th>
                <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="provider in store.providers"
                :key="provider.uuid"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4">
                  <p class="font-medium text-slate-900">{{ provider.name }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">{{ provider.default_model || 'No model' }}</p>
                </td>
                <td class="px-5 py-4 text-slate-700">{{ provider.driver_label || provider.driver }}</td>
                <td class="px-5 py-4">
                  <span
                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="healthClass(provider.health_status)"
                  >
                    {{ provider.health_status || 'unknown' }}
                  </span>
                </td>
                <td class="px-5 py-4 text-xs text-slate-600">
                  <span v-if="provider.is_default">default · </span>
                  <span>{{ provider.is_enabled ? 'enabled' : 'disabled' }}</span>
                </td>
                <td class="px-5 py-4">
                  <div class="relative flex justify-end">
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                      :aria-expanded="openMenuId === provider.uuid"
                      aria-haspopup="menu"
                      aria-label="Open actions"
                      @click.stop="toggleMenu(provider.uuid, $event)"
                    >
                      <EllipsisVerticalIcon class="h-5 w-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeProvider"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onEdit(activeProvider)"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onTest(activeProvider)"
        >
          <BeakerIcon class="h-4 w-4 text-slate-400" />
          Test
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activeProvider)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete provider"
      :message="`Delete provider “${pendingDelete?.name || 'this provider'}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
  BeakerIcon,
  EllipsisVerticalIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const toast = useToast();
const editingUuid = ref(null);
const openMenuId = ref(null);
const menuStyle = ref({});
const pendingDelete = ref(null);

const activeProvider = computed(
  () => store.providers.find((provider) => provider.uuid === openMenuId.value) || null,
);

const driverOptions = computed(() =>
  (store.catalog.drivers || []).map((driver) => ({
    value: driver.value,
    label: `${driver.label}${driver.registered ? '' : ' (unregistered)'}`,
  })),
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

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
    default_model: 'gemini-flash-latest',
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
  return 'bg-zinc-100 text-slate-600';
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 8 + 3 * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onEdit(provider) {
  closeMenu();
  editProvider(provider);
}

async function onTest(provider) {
  closeMenu();
  await testProvider(provider);
}

async function onDelete(provider) {
  closeMenu();
  pendingDelete.value = provider;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await removeProvider(pendingDelete.value);
  pendingDelete.value = null;
}

function onDocumentClick() {
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
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
    if (result?.ok) {
      store.successMessage = result?.message || 'Connection test passed.';
    } else {
      store.error = result?.message || 'Connection test failed.';
    }
    await store.fetchProviders();
  } catch (error) {
    store.error = error?.response?.data?.message || error?.message || 'Connection test failed.';
  }
}

async function removeProvider(provider) {
  try {
    await store.deleteProvider(provider.uuid);
    if (editingUuid.value === provider.uuid) {
      resetForm();
    }
    await store.fetchProviders();
  } catch {
    // store.error already set
  }
}

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  await store.fetchCatalog();
  await store.fetchProviders();
  if (!editingUuid.value) {
    applyPresetFields('gemini', { fillName: true });
  }
  document.addEventListener('click', onDocumentClick);
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize);
});
</script>
