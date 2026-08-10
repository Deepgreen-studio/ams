<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openUpload"
      >
        Upload
      </button>
    </Teleport>

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-[12px] bg-white px-4 py-3 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <DocumentFolderSidebar
        v-model="selectedFolder"
        :folders="store.folders"
        @update:model-value="onFolderChange"
      />

      <DocumentTable
        :documents="store.documents"
        :loading="store.loading"
        :customer-id="route.params.id"
        @edit="openEdit"
        @delete="openDelete"
        @download="onDownload"
      >
        <template #toolbar>
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative min-w-0 flex-1 lg:max-w-sm">
              <MagnifyingGlassIcon
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              />
              <input
                v-model="search"
                type="search"
                placeholder="Name, file, notes..."
                class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
                @keyup.enter="onSearch"
              />
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <SelectBox
                v-model="status"
                wrapper-class="min-w-[9.5rem]"
                :options="statusOptions"
                @change="onSearch"
              />
              <button
                type="button"
                class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
                @click="onSearch"
              >
                Apply
              </button>
              <button
                type="button"
                class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
                @click="onReset"
              >
                Reset
              </button>
            </div>
          </div>
        </template>

        <template #empty-action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset
          </button>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="openUpload"
          >
            Upload document
          </button>
        </template>

        <template #footer>
          <Pagination
            :meta="store.meta"
            :loading="store.loading"
            @change="onPageChange"
            @per-page="onPerPageChange"
          />
        </template>
      </DocumentTable>
    </div>

    <DocumentFormModal
      :open="formOpen"
      :loading="store.saving"
      :document="editingDocument"
      :default-category="selectedFolder || 'contracts'"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete document"
      :message="`Soft delete ${pendingDelete?.name || 'this document'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import DocumentFolderSidebar from '@/modules/customers/components/DocumentFolderSidebar.vue';
import DocumentFormModal from '@/modules/customers/components/DocumentFormModal.vue';
import DocumentTable from '@/modules/customers/components/DocumentTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerDocumentsStore } from '@/modules/customers/stores/documents';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCustomerDocumentsStore();
const pendingDelete = ref(null);
const editingDocument = ref(null);
const formOpen = ref(false);
const selectedFolder = ref('');
const search = ref('');
const status = ref('');

const statusOptions = [
  { value: '', label: 'Status: All' },
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'expired', label: 'Expired' },
  { value: 'archived', label: 'Archived' },
];

const statCards = computed(() => {
  const stats = store.statistics || {};
  return [
    { label: 'Total', value: stats.total ?? 0 },
    { label: 'Active', value: stats.active ?? 0 },
    { label: 'Expired', value: stats.expired ?? 0 },
    { label: 'Expiring soon', value: stats.expiring_soon ?? 0 },
  ];
});

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchLibrary({ customer: route.params.id, page: 1 });
});

function currentQuery(extra = {}) {
  return {
    customer: route.params.id,
    category: selectedFolder.value,
    search: search.value,
    status: status.value,
    ...extra,
  };
}

function onFolderChange(category) {
  selectedFolder.value = category;
  store.fetchLibrary(currentQuery({ category, page: 1 }));
}

function onSearch() {
  store.fetchLibrary(currentQuery({ page: 1 }));
}

function onReset() {
  search.value = '';
  status.value = '';
  selectedFolder.value = '';
  store.resetFilters(route.params.id);
  store.fetchLibrary({ customer: route.params.id, page: 1 });
}

function onPageChange(page) {
  store.fetchLibrary(currentQuery({ page }));
}

function onPerPageChange(perPage) {
  store.fetchLibrary(currentQuery({ per_page: perPage, page: 1 }));
}

function openUpload() {
  store.clearMessages();
  editingDocument.value = null;
  formOpen.value = true;
}

function openEdit(item) {
  store.clearMessages();
  editingDocument.value = item;
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  editingDocument.value = null;
  store.clearMessages();
}

async function onSave(payload) {
  try {
    if (editingDocument.value?.uuid) {
      await store.updateDocument(editingDocument.value.uuid, payload);
    } else {
      const formData = new FormData();
      formData.append('customer_id', route.params.id);
      formData.append('category', payload.category);
      formData.append('status', payload.status || 'active');
      formData.append('file', payload.file);
      if (payload.name) formData.append('name', payload.name);
      if (payload.notes) formData.append('notes', payload.notes);
      if (payload.expires_at) {
        formData.append('expires_at', new Date(payload.expires_at).toISOString());
      }
      await store.uploadDocument(formData);
    }
    formOpen.value = false;
    editingDocument.value = null;
    await store.fetchLibrary(currentQuery());
  } catch {
    // Field errors stay in the modal via the store.
  }
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function onDownload(item) {
  await store.downloadDocument(item.uuid, item.original_filename || item.name);
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.archiveDocument(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await store.fetchLibrary(currentQuery());
}
</script>
