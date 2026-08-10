<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'companies.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create company
      </RouterLink>
    </Teleport>

    <div
      v-if="companiesStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ companiesStore.successMessage }}
    </div>
    <div
      v-if="companiesStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ companiesStore.error }}
    </div>

    <CompanyTable
      :companies="companiesStore.companies"
      :loading="companiesStore.loading"
      :sort-by="companiesStore.filters.sort_by"
      :sort-dir="companiesStore.filters.sort_dir"
      @sort="onSort"
      @delete="openDelete"
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
          Reset
        </button>
        <RouterLink
          :to="{ name: 'companies.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create company
        </RouterLink>
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
      title="Delete company"
      :message="`Soft delete ${pendingDelete?.company_name || 'this company'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="companiesStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CompanyTable from '@/modules/companies/components/CompanyTable.vue';
import SearchFilters from '@/modules/companies/components/SearchFilters.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const companiesStore = useCompaniesStore();
const pendingDelete = ref(null);

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
</script>
