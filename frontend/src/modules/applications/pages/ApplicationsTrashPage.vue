<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'applications.index' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to applications
      </RouterLink>
    </Teleport>

    <div
      v-if="applicationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ applicationsStore.successMessage }}
    </div>
    <div
      v-if="applicationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ applicationsStore.error }}
    </div>

    <ApplicationTable
      :applications="applicationsStore.applications"
      :loading="applicationsStore.loading"
      :sort-by="applicationsStore.filters.sort_by"
      :sort-dir="applicationsStore.filters.sort_dir"
      @sort="onSort"
      @restore="confirmRestore"
      @force-delete="openForceDelete"
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
              placeholder="Search deleted applications..."
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
          :meta="applicationsStore.meta"
          :loading="applicationsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </ApplicationTable>

    <DeleteConfirmation
      :open="Boolean(pendingForceDelete)"
      title="Permanently delete application"
      :message="`Permanently delete ${pendingForceDelete?.name || 'this application'}? This cannot be undone.`"
      confirm-label="Permanent Delete"
      :loading="applicationsStore.saving"
      @cancel="pendingForceDelete = null"
      @confirm="confirmForceDelete"
    />
  </div>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ApplicationTable from '@/modules/applications/components/ApplicationTable.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const applicationsStore = useApplicationsStore();
const search = ref('');
const pendingForceDelete = ref(null);
const previousFilters = ref(null);
let searchTimer = null;

onMounted(() => {
  previousFilters.value = { ...applicationsStore.filters };
  loadTrash();
});

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
  if (previousFilters.value) {
    applicationsStore.filters = {
      ...previousFilters.value,
      trashed: previousFilters.value.trashed === 'only' ? '' : previousFilters.value.trashed,
    };
  }
});

function loadTrash(overrides = {}) {
  return applicationsStore.fetchApplications({
    trashed: 'only',
    status: '',
    platform: '',
    category: '',
    visibility: '',
    company: '',
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
  loadTrash({ page, sort_by: applicationsStore.filters.sort_by, sort_dir: applicationsStore.filters.sort_dir });
}

function onPerPageChange(perPage) {
  loadTrash({
    per_page: perPage,
    page: 1,
    sort_by: applicationsStore.filters.sort_by,
    sort_dir: applicationsStore.filters.sort_dir,
  });
}

function onSort(column) {
  const sortDir =
    applicationsStore.filters.sort_by === column && applicationsStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';
  loadTrash({ sort_by: column, sort_dir: sortDir, page: 1 });
}

async function confirmRestore(application) {
  await applicationsStore.restoreApplication(application.uuid);
  await loadTrash({
    page: applicationsStore.filters.page,
    sort_by: applicationsStore.filters.sort_by,
    sort_dir: applicationsStore.filters.sort_dir,
  });
}

function openForceDelete(application) {
  pendingForceDelete.value = application;
}

async function confirmForceDelete() {
  if (!pendingForceDelete.value) {
    return;
  }

  await applicationsStore.forceDeleteApplication(pendingForceDelete.value.uuid);
  pendingForceDelete.value = null;
  await loadTrash({
    page: applicationsStore.filters.page,
    sort_by: applicationsStore.filters.sort_by,
    sort_dir: applicationsStore.filters.sort_dir,
  });
}
</script>
