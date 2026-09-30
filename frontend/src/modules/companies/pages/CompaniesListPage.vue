<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="can('companies.view-trash')"
        :to="{ name: 'companies.trash' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Soft Deleted
      </RouterLink>
      <RouterLink
        v-if="can('companies.create')"
        :to="{ name: 'companies.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create Company
      </RouterLink>
    </Teleport>

    <CompanyTable
      :companies="companiesStore.companies"
      :loading="companiesStore.loading"
      :sort-by="companiesStore.filters.sort_by"
      :sort-dir="companiesStore.filters.sort_dir"
      @sort="onSort"
      @delete="openDelete"
      @restore="confirmRestore"
      @status-change="onStatusChange"
    >
      <template #toolbar>
        <SearchFilters :model-value="companiesStore.filters" @submit="onFilter" @reset="onReset" />
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset Filter
        </button>
      </template>

      <template #footer>
        <Pagination
          :meta="companiesStore.meta"
          :loading="companiesStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </CompanyTable>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Soft delete company"
      :message="`Soft delete ${pendingDelete?.company_name || 'this company'}? It can be restored later.`"
      confirm-label="Soft Delete"
      :loading="companiesStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline';
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CompanyTable from '@/modules/companies/components/CompanyTable.vue';
import SearchFilters from '@/modules/companies/components/SearchFilters.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const companiesStore = useCompaniesStore();
const { can } = usePermissions();
const toast = useToast();
const pendingDelete = ref(null);

watch(
  () => companiesStore.successMessage,
  (message) => {
    if (message) {
      toast.success(message);
      companiesStore.successMessage = null;
    }
  },
);

watch(
  () => companiesStore.error,
  (message) => {
    if (message) {
      toast.error(message);
      companiesStore.error = null;
    }
  },
);

onMounted(() => {
  companiesStore.fetchCompanies();
});

function onFilter(filters) {
  companiesStore.fetchCompanies(filters);
}

function onReset() {
  companiesStore.resetFilters();
  companiesStore.fetchCompanies();
}

function onPageChange(page) {
  companiesStore.fetchCompanies({ page });
}

function onPerPageChange(perPage) {
  companiesStore.fetchCompanies({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    companiesStore.filters.sort_by === column && companiesStore.filters.sort_dir === 'asc'
      ? 'desc'
      : 'asc';

  companiesStore.fetchCompanies({ sort_by: column, sort_dir: sortDir, page: 1 });
}

function openDelete(company) {
  pendingDelete.value = company;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await companiesStore.deleteCompany(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await companiesStore.fetchCompanies();
}

async function confirmRestore(company) {
  await companiesStore.restoreCompany(company.uuid);
  await companiesStore.fetchCompanies();
}

async function onStatusChange(company, status) {
  if (!company?.uuid || company.status === status) {
    return;
  }

  await companiesStore.updateCompany(company.uuid, { status });
}
</script>
