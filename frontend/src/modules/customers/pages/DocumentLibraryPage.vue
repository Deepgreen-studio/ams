<template>
  <div>
    <PageHeader
      title="Document library"
      :description="`Contracts, NDAs, invoices, and files for ${customerName}.`"
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{
            name: 'customers.documents.upload',
            params: { id: route.params.id },
            query: store.filters.category ? { category: store.filters.category } : {},
          }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Upload
        </RouterLink>
      </template>
    </PageHeader>

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <DocumentFolderSidebar
        v-model="selectedFolder"
        :folders="store.folders"
        @update:model-value="onFolderChange"
      />

      <div class="space-y-4">
        <form
          class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-end"
          @submit.prevent="onSearch"
        >
          <div class="min-w-[12rem] flex-1">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
              >Search</label
            >
            <input
              v-model="search"
              type="search"
              placeholder="Name, file, notes..."
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
            />
          </div>
          <div class="w-full md:w-40">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
              >Status</label
            >
            <select
              v-model="status"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            >
              <option value="">All</option>
              <option value="draft">Draft</option>
              <option value="active">Active</option>
              <option value="expired">Expired</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <button
            type="submit"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Filter
          </button>
        </form>

        <DocumentTable
          :documents="store.documents"
          :loading="store.loading"
          :customer-id="route.params.id"
          @archive="openArchive"
          @download="onDownload"
        >
          <template #empty-action>
            <RouterLink
              :to="{ name: 'customers.documents.upload', params: { id: route.params.id } }"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            >
              Upload document
            </RouterLink>
          </template>
        </DocumentTable>

        <Pagination :meta="store.meta" :loading="store.loading" @change="onPageChange" />
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive document"
      :message="`Archive ${pendingArchive?.name || 'this document'}?`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="pendingArchive = null"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import DocumentFolderSidebar from '@/modules/customers/components/DocumentFolderSidebar.vue';
import DocumentTable from '@/modules/customers/components/DocumentTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerDocumentsStore } from '@/modules/customers/stores/documents';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCustomerDocumentsStore();
const pendingArchive = ref(null);
const selectedFolder = ref('');
const search = ref('');
const status = ref('');

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

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

function onFolderChange(category) {
  selectedFolder.value = category;
  store.fetchLibrary({ customer: route.params.id, category, page: 1 });
}

function onSearch() {
  store.fetchLibrary({
    customer: route.params.id,
    category: selectedFolder.value,
    search: search.value,
    status: status.value,
    page: 1,
  });
}

function onPageChange(page) {
  store.fetchLibrary({
    customer: route.params.id,
    category: selectedFolder.value,
    search: search.value,
    status: status.value,
    page,
  });
}

function openArchive(item) {
  pendingArchive.value = item;
}

async function onDownload(item) {
  await store.downloadDocument(item.uuid, item.original_filename || item.name);
}

async function confirmArchive() {
  if (!pendingArchive.value) return;
  await store.archiveDocument(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await store.fetchLibrary({
    customer: route.params.id,
    category: selectedFolder.value,
    search: search.value,
    status: status.value,
  });
}
</script>
