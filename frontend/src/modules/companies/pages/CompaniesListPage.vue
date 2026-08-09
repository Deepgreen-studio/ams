<template>
  <div>
    <!-- <PageHeader title="Companies" description="Manage organizations, branding, and structure.">
      <template #actions>
        <RouterLink :to="{ name: 'companies.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
          Create company
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink :to="{ name: 'companies.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
          Create company
        </RouterLink>
    </Teleport>

    <div v-if="companiesStore.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ companiesStore.successMessage }}
    </div>
    <div v-if="companiesStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ companiesStore.error }}
    </div>

    <div class="space-y-4">
      <SearchFilters :model-value="companiesStore.filters" @submit="onFilter" @reset="onReset" />

      <CompanyTable :companies="companiesStore.companies" :loading="companiesStore.loading" @delete="openDelete">
        <template #empty-action>
          <RouterLink :to="{ name: 'companies.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
            Create company
          </RouterLink>
        </template>
      </CompanyTable>

      <Pagination :meta="companiesStore.meta" :loading="companiesStore.loading" @change="onPageChange" />
    </div>

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
// import PageHeader from '@/components/ui/PageHeader.vue';
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
  companiesStore.filters = {
    search: '',
    status: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  };
  companiesStore.fetchCompanies();
}

function onPageChange(page) {
  companiesStore.fetchCompanies({ page });
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
