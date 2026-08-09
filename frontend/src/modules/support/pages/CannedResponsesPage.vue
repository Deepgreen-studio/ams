<template>
  <div>
    <!-- <PageHeader
      title="Canned Responses"
      description="Personal and shared reply templates for support conversations"
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          New response
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          New response
        </button>
    </Teleport>

    <SupportSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search title, shortcut, or body…"
        class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="reload"
      />
      <select v-model="filters.visibility" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All visibility</option>
        <option value="personal">Personal</option>
        <option value="shared">Shared</option>
      </select>
      <select v-model="filters.is_active" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="reload"
      >
        Search
      </button>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="store.items.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">
      No canned responses found.
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Title</th>
            <th class="px-4 py-3">Visibility</th>
            <th class="px-4 py-3">Shortcut</th>
            <th class="px-4 py-3">Usage</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.items" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="mt-0.5 line-clamp-1 text-xs text-slate-500">{{ plainPreview(item.body) }}</p>
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                :class="item.visibility === 'shared' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700'"
              >
                {{ item.visibility_label || item.visibility }}
              </span>
            </td>
            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ item.shortcut || '—' }}</td>
            <td class="px-4 py-3">{{ item.usage_count }}</td>
            <td class="px-4 py-3">
              <span :class="item.is_active ? 'text-emerald-700' : 'text-slate-400'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button type="button" class="mr-2 text-brand-700 hover:underline" @click="openEdit(item)">Edit</button>
              <button type="button" class="text-rose-600 hover:underline" @click="confirmDelete(item)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="showForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="closeForm"
    >
      <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
        <div class="border-b border-slate-200 px-5 py-4">
          <h3 class="text-base font-semibold text-slate-900">
            {{ editing ? 'Edit canned response' : 'New canned response' }}
          </h3>
        </div>
        <form class="space-y-4 px-5 py-4" @submit.prevent="save">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Title</label>
            <input v-model="form.title" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Shortcut</label>
              <input v-model="form.shortcut" placeholder="e.g. hello" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Visibility</label>
              <select v-model="form.visibility" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="personal">Personal</option>
                <option value="shared">Shared (team)</option>
              </select>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Body</label>
            <TicketReplyEditor v-model="form.body" :editable="!store.saving" />
          </div>
          <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
            Active
          </label>
          <p v-if="formError" class="text-sm text-rose-600">{{ formError }}</p>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="closeForm">
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              {{ store.saving ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketReplyEditor from '@/modules/support/components/TicketReplyEditor.vue';
import { useCannedResponsesStore } from '@/modules/support/stores/cannedResponses';

const store = useCannedResponsesStore();
const showForm = ref(false);
const editing = ref(null);
const formError = ref('');

const filters = reactive({
  search: '',
  visibility: '',
  is_active: '',
});

const form = reactive({
  title: '',
  shortcut: '',
  body: '',
  visibility: 'personal',
  is_active: true,
});

const statCards = computed(() => [
  { label: 'Total', value: store.statistics?.total ?? 0 },
  { label: 'Personal', value: store.statistics?.personal ?? 0 },
  { label: 'Shared', value: store.statistics?.shared ?? 0 },
  { label: 'Active', value: store.statistics?.active ?? 0 },
]);

onMounted(async () => {
  await Promise.all([store.fetchDashboard(), reload()]);
});

async function reload() {
  const params = {
    search: filters.search || undefined,
    visibility: filters.visibility || undefined,
    is_active: filters.is_active === '' ? undefined : filters.is_active,
    per_page: 50,
    sort_by: 'title',
  };
  await store.fetchList(params);
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
    } else {
      await store.create(payload);
    }
    closeForm();
    await Promise.all([store.fetchDashboard(), reload()]);
  } catch (error) {
    formError.value = error?.response?.data?.message || store.error || 'Unable to save';
  }
}

async function confirmDelete(item) {
  if (!window.confirm(`Delete “${item.title}”?`)) return;
  await store.remove(item.uuid);
  await Promise.all([store.fetchDashboard(), reload()]);
}
</script>
