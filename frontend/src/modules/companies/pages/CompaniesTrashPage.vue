<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'companies.index' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to companies
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

    <CompanyTrashTable
      :companies="companiesStore.companies"
      :loading="companiesStore.loading"
      :sort-by="companiesStore.filters.sort_by"
      :sort-dir="companiesStore.filters.sort_dir"
      @sort="onSort"
      @restore="confirmRestore"
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
              placeholder="Search deleted companies..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @input="onSearchInput"
              @search="onSearchInput"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applySearch"
            >
              Apply Filter
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetSearch"
            >
              Reset Filter
            </button>
          </div>
        </div>
      </template>

      <template #footer>
        <Pagination
          :meta="companiesStore.meta"
          :loading="companiesStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </CompanyTrashTable>
  </div>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from '@/modules/users/components/Pagination.vue';
import CompanyTrashTable from '@/modules/companies/components/CompanyTrashTable.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const companiesStore = useCompaniesStore();
const search = ref('');
const previousFilters = ref(null);
let searchTimer = null;

onMounted(() => {
  previousFilters.value = { ...companiesStore.filters };
  loadTrash();
});

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
  if (previousFilters.value) {
    companiesStore.filters = {
      ...previousFilters.value,
      trashed: previousFilters.value.trashed === 'only' ? '' : previousFilters.value.trashed,
    };
  }
});

function loadTrash(overrides = {}) {
  return companiesStore.fetchCompanies({
    trashed: 'only',
    status: '',
    search: search.value.trim(),
    sort_by: 'deleted_at',
    sort_dir: 'desc',
    page: 1,
    ...overrides,
  });
}

function applySearch() {
  loadTrash({ search: search.value.trim(), page: 1 });
}

function resetSearch() {
  search.value = '';
  loadTrash({ search: '', page: 1 });
}

function onSearchInput() {
  window.clearTimeout(searchTimer);
  const delay = search.value.trim() ? 300 : 0;
  searchTimer = window.setTimeout(() => applySearch(), delay);
}

function onPageChange(page) {
  loadTrash({ page, sort_by: companiesStore.filters.sort_by, sort_dir: companiesStore.filters.sort_dir });
}

function onPerPageChange(perPage) {
  loadTrash({
    per_page: perPage,
    page: 1,
    sort_by: companiesStore.filters.sort_by,
    sort_dir: companiesStore.filters.sort_dir,
  });
}

function onSort(column) {
  const sortDir =
    companiesStore.filters.sort_by === column && companiesStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';
  loadTrash({ sort_by: column, sort_dir: sortDir, page: 1 });
}

async function confirmRestore(company) {
  await companiesStore.restoreCompany(company.uuid);
  await loadTrash({
    page: companiesStore.filters.page,
    sort_by: companiesStore.filters.sort_by,
    sort_dir: companiesStore.filters.sort_dir,
  });
}
</script>
