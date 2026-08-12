<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="startCreate"
      >
        New prompt
      </button>
    </Teleport>

    <AiSubnav />

    <div
      v-if="showForm"
      class="mb-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100"
    >
      <h2 class="mb-4 text-base font-semibold text-slate-900">
        {{ editingUuid ? 'Edit prompt' : 'Create prompt' }}
      </h2>
      <form class="grid gap-4 md:grid-cols-2" @submit.prevent="save">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
          <input
            v-model="form.name"
            required
            class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Feature</label>
          <SelectBox
            v-model="form.feature"
            :options="formFeatureOptions"
            placeholder="Select feature"
          />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">System prompt</label>
          <textarea
            v-model="form.system_prompt"
            rows="3"
            class="w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">User template</label>
          <textarea
            v-model="form.user_template"
            rows="3"
            class="w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div class="md:col-span-2 flex flex-wrap gap-2">
          <button
            type="submit"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving"
          >
            Save
          </button>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="showForm = false"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search prompts…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.feature"
              wrapper-class="min-w-[11rem]"
              :options="featureOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.status"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.prompts.length"
        title="No prompts found"
        description="Try adjusting your filters or create a new prompt template."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="startCreate"
          >
            New prompt
          </button>
        </template>
      </EmptyState>

      <template v-else>
        <div class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Name</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Feature</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Version</th>
                <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="prompt in store.prompts"
                :key="prompt.uuid"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4">
                  <p class="font-medium text-slate-900">{{ prompt.name }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">{{ prompt.key }}</p>
                </td>
                <td class="px-5 py-4 text-slate-700">{{ prompt.feature_label || prompt.feature }}</td>
                <td class="px-5 py-4">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                    :class="statusClass(prompt.status)"
                  >
                    <span
                      class="h-1.5 w-1.5 shrink-0 rounded-full"
                      :class="statusDotClass(prompt.status)"
                    />
                    {{ prompt.status_label || prompt.status }}
                  </span>
                </td>
                <td class="px-5 py-4 text-slate-600">v{{ prompt.version }}</td>
                <td class="px-5 py-4">
                  <div class="relative flex justify-end">
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                      :aria-expanded="openMenuId === prompt.uuid"
                      aria-haspopup="menu"
                      aria-label="Open actions"
                      @click.stop="toggleMenu(prompt.uuid, $event)"
                    >
                      <EllipsisVerticalIcon class="h-5 w-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="store.promptMeta?.total"
          class="border-t border-zinc-100 px-6 py-4 sm:px-8"
        >
          <Pagination
            :meta="store.promptMeta"
            :loading="store.loading"
            @change="onPageChange"
            @per-page="onPerPageChange"
          />
        </div>
      </template>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activePrompt"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onEdit(activePrompt)"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </button>
        <button
          v-if="activePrompt.status !== 'published'"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50 disabled:opacity-50"
          role="menuitem"
          :disabled="store.saving"
          @click="onPublish(activePrompt)"
        >
          <CheckCircleIcon class="h-4 w-4 text-slate-400" />
          Publish
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activePrompt)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete prompt"
      :message="`Delete prompt “${pendingDelete?.name || 'this prompt'}”? This cannot be undone.`"
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
  CheckCircleIcon,
  EllipsisVerticalIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const toast = useToast();
const showForm = ref(false);
const editingUuid = ref(null);
const openMenuId = ref(null);
const menuStyle = ref({});
const pendingDelete = ref(null);

const filters = reactive({
  search: '',
  feature: '',
  status: '',
  page: 1,
  per_page: 20,
});

const form = reactive({
  name: '',
  feature: 'chat_assistant',
  system_prompt: '',
  user_template: '',
});

const activePrompt = computed(
  () => store.prompts.find((prompt) => prompt.uuid === openMenuId.value) || null,
);

const featureOptions = computed(() => [
  { value: '', label: 'All features' },
  ...(store.catalog.features || []).map((feature) => ({
    value: feature.value,
    label: feature.label,
  })),
]);

const formFeatureOptions = computed(() =>
  (store.catalog.features || []).map((feature) => ({
    value: feature.value,
    label: feature.label,
  })),
);

const statusOptions = computed(() => [
  { value: '', label: 'All statuses' },
  ...(store.catalog.prompt_statuses || []).map((status) => ({
    value: status.value,
    label: status.label,
  })),
]);

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

function statusClass(status) {
  if (status === 'published') return 'bg-emerald-50 text-emerald-700';
  if (status === 'draft') return 'bg-amber-50 text-amber-700';
  if (status === 'archived') return 'bg-zinc-100 text-slate-600';
  return 'bg-zinc-100 text-slate-600';
}

function statusDotClass(status) {
  if (status === 'published') return 'bg-emerald-500';
  if (status === 'draft') return 'bg-amber-500';
  if (status === 'archived') return 'bg-slate-400';
  return 'bg-slate-400';
}

function startCreate() {
  editingUuid.value = null;
  form.name = '';
  form.feature = 'chat_assistant';
  form.system_prompt = '';
  form.user_template = '';
  showForm.value = true;
}

function edit(prompt) {
  editingUuid.value = prompt.uuid;
  form.name = prompt.name;
  form.feature = prompt.feature;
  form.system_prompt = prompt.system_prompt || '';
  form.user_template = prompt.user_template || '';
  showForm.value = true;
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const itemCount = store.prompts.find((p) => p.uuid === id)?.status === 'published' ? 2 : 3;
  const menuHeight = 8 + itemCount * 36;
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

function onEdit(prompt) {
  closeMenu();
  edit(prompt);
}

async function onPublish(prompt) {
  closeMenu();
  await publish(prompt);
}

function onDelete(prompt) {
  closeMenu();
  pendingDelete.value = prompt;
}

function onDocumentClick() {
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
}

async function load() {
  await store.fetchPrompts({
    search: filters.search || undefined,
    feature: filters.feature || undefined,
    status: filters.status || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.feature = '';
  filters.status = '';
  filters.page = 1;
  load();
}

function onPageChange(page) {
  filters.page = page;
  load();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

async function save() {
  const payload = { ...form };
  try {
    if (editingUuid.value) {
      await store.updatePrompt(editingUuid.value, payload);
    } else {
      await store.createPrompt(payload);
    }
    showForm.value = false;
    await load();
  } catch {
    // store.error already set
  }
}

async function publish(prompt) {
  try {
    await store.publishPrompt(prompt.uuid);
    await load();
  } catch {
    // store.error already set
  }
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  try {
    await store.deletePrompt(pendingDelete.value.uuid);
    pendingDelete.value = null;
    await load();
  } catch {
    // store.error already set
  }
}

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  await store.fetchCatalog();
  await load();
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
