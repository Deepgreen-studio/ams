<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        <PlusIcon class="h-4 w-4" />
        New response
      </button>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !store.statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <div class="mb-4">
          <h2 class="text-base font-semibold text-slate-900">Canned responses</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Personal and shared reply templates for support conversations.
          </p>
        </div>
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
          @submit.prevent="onSearch"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="search"
              type="search"
              placeholder="Search title, shortcut, or body…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="visibility"
              wrapper-class="min-w-[10.5rem]"
              :options="visibilityOptions"
              @change="onFilterChange"
            />
            <SelectBox
              v-model="status"
              wrapper-class="min-w-[10.5rem]"
              :options="statusOptions"
              @change="onFilterChange"
            />
            <button
              type="submit"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
            >
              Search
            </button>
          </div>
        </form>
      </div>

      <div v-if="store.loading && !store.items.length" class="space-y-3 px-6 py-5 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.items.length"
        title="No canned responses found"
        description="Create a personal or shared reply template to speed up ticket replies."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset filters
          </button>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="openCreate"
          >
            New response
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Title</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Visibility</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Shortcut
              </th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Usage
              </th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.items"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.title }}</p>
                <p class="mt-0.5 line-clamp-1 text-xs text-slate-500">{{ plainPreview(item.body) }}</p>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                  :class="
                    item.visibility === 'shared'
                      ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
                      : 'bg-slate-50 text-slate-700 ring-slate-500/20'
                  "
                >
                  {{ item.visibility_label || item.visibility }}
                </span>
              </td>
              <td class="hidden px-5 py-4 font-mono text-xs text-slate-600 md:table-cell">
                {{ item.shortcut || '—' }}
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">{{ item.usage_count ?? 0 }}</td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                  :class="
                    item.is_active
                      ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                      : 'bg-slate-50 text-slate-600 ring-slate-500/20'
                  "
                >
                  {{ item.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-100"
                    @click="openEdit(item)"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                    @click="pendingDelete = item"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <div
      v-if="showForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="closeForm"
    >
      <div class="w-full max-w-2xl overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h3 class="text-base font-semibold text-slate-900">
            {{ editing ? 'Edit canned response' : 'New canned response' }}
          </h3>
          <p class="mt-0.5 text-xs text-slate-500">
            Use shortcuts in ticket replies to insert this template.
          </p>
        </div>
        <form class="space-y-4 px-6 py-5" @submit.prevent="save">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Title</label>
            <input
              v-model="form.title"
              required
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Shortcut</label>
              <input
                v-model="form.shortcut"
                placeholder="e.g. hello"
                class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Visibility</label>
              <SelectBox v-model="form.visibility" :options="formVisibilityOptions" />
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Body</label>
            <TicketReplyEditor v-model="form.body" :editable="!store.saving" />
          </div>
          <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_active" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
            Active
          </label>
          <p v-if="formError" class="text-sm text-rose-600">{{ formError }}</p>
          <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4">
            <button
              type="button"
              class="inline-flex h-10 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="closeForm"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="inline-flex h-10 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              {{ store.saving ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete canned response"
      :message="`Delete “${pendingDelete?.title || 'this response'}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
  CheckCircleIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  UserGroupIcon,
  UserIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketReplyEditor from '@/modules/support/components/TicketReplyEditor.vue';
import { useCannedResponsesStore } from '@/modules/support/stores/cannedResponses';

const store = useCannedResponsesStore();
const toast = useToast();
const showForm = ref(false);
const editing = ref(null);
const formError = ref('');
const pendingDelete = ref(null);
const search = ref('');
const visibility = ref('');
const status = ref('');
const perPage = ref(10);

const visibilityOptions = [
  { value: '', label: 'All visibility' },
  { value: 'personal', label: 'Personal' },
  { value: 'shared', label: 'Shared' },
];

const statusOptions = [
  { value: '', label: 'All status' },
  { value: '1', label: 'Active' },
  { value: '0', label: 'Inactive' },
];

const formVisibilityOptions = [
  { value: 'personal', label: 'Personal' },
  { value: 'shared', label: 'Shared (team)' },
];

const form = reactive({
  title: '',
  shortcut: '',
  body: '',
  visibility: 'personal',
  is_active: true,
});

const statCards = computed(() => {
  const total = store.statistics?.total ?? 0;
  const personal = store.statistics?.personal ?? 0;
  const shared = store.statistics?.shared ?? 0;
  const active = store.statistics?.active ?? 0;

  return [
    {
      label: 'Total',
      value: total,
      hint: 'Templates you can use',
      icon: ChatBubbleLeftRightIcon,
      iconBg: total ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: total ? 'text-brand-500' : 'text-slate-500',
    },
    {
      label: 'Personal',
      value: personal,
      hint: 'Visible only to you',
      icon: UserIcon,
      iconBg: personal ? 'bg-slate-100' : 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
    {
      label: 'Shared',
      value: shared,
      hint: 'Available to the team',
      icon: UserGroupIcon,
      iconBg: shared ? 'bg-indigo-50' : 'bg-zinc-100',
      iconColor: shared ? 'text-indigo-500' : 'text-slate-500',
    },
    {
      label: 'Active',
      value: active,
      hint: active ? 'Ready to insert in replies' : 'No active templates',
      icon: CheckCircleIcon,
      iconBg: active ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: active ? 'text-emerald-500' : 'text-slate-500',
    },
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message || showForm.value) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(async () => {
  store.error = null;
  await Promise.all([store.fetchDashboard(), loadList(1)]).catch(() => {});
});

function listParams(page = store.meta?.current_page || 1) {
  return {
    search: search.value || undefined,
    visibility: visibility.value || undefined,
    is_active: status.value === '' ? undefined : status.value,
    page,
    per_page: perPage.value,
    sort_by: 'title',
  };
}

async function loadList(page = 1) {
  await store.fetchList(listParams(page));
}

function onSearch() {
  loadList(1).catch(() => {});
}

function onFilterChange() {
  loadList(1).catch(() => {});
}

function onPageChange(page) {
  loadList(page).catch(() => {});
}

function onPerPage(value) {
  perPage.value = value;
  loadList(1).catch(() => {});
}

function resetFilters() {
  search.value = '';
  visibility.value = '';
  status.value = '';
  loadList(1).catch(() => {});
}

function plainPreview(html) {
  return String(html || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 120);
}

function resetForm() {
  form.title = '';
  form.shortcut = '';
  form.body = '';
  form.visibility = 'personal';
  form.is_active = true;
  formError.value = '';
}

function openCreate() {
  editing.value = null;
  resetForm();
  showForm.value = true;
}

function openEdit(item) {
  editing.value = item;
  form.title = item.title;
  form.shortcut = item.shortcut || '';
  form.body = item.body || '';
  form.visibility = item.visibility || 'personal';
  form.is_active = !!item.is_active;
  formError.value = '';
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editing.value = null;
  formError.value = '';
}

async function save() {
  formError.value = '';
  const payload = {
    title: form.title,
    shortcut: form.shortcut || null,
    body: form.body,
    visibility: form.visibility,
    is_active: form.is_active,
  };

  try {
    if (editing.value) {
      await store.update(editing.value.uuid, payload);
      toast.success('Canned response updated.');
    } else {
      await store.create(payload);
      toast.success('Canned response created.');
    }
    closeForm();
    await Promise.all([store.fetchDashboard(), loadList()]).catch(() => {});
  } catch (error) {
    const payloadError = error?.response?.data ?? error;
    const firstFieldError = payloadError?.errors
      ? Object.values(payloadError.errors).flat().find(Boolean)
      : null;
    formError.value = firstFieldError || payloadError?.message || store.error || 'Unable to save';
  }
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  try {
    await store.remove(pendingDelete.value.uuid);
    toast.success('Canned response deleted.');
    pendingDelete.value = null;
    await Promise.all([store.fetchDashboard(), loadList()]).catch(() => {});
  } catch {
    toast.error(store.error || 'Unable to delete canned response');
  }
}
</script>
