<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'integrations.index' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to integrations
      </RouterLink>
    </Teleport>

    <IntegrationsHubSubnav />

    <div
      v-if="integrationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ integrationsStore.successMessage }}
    </div>
    <div
      v-if="integrationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ integrationsStore.error }}
    </div>

    <IntegrationTable
      :integrations="integrationsStore.integrations"
      :loading="integrationsStore.loading"
      :sort-by="integrationsStore.filters.sort_by"
      :sort-dir="integrationsStore.filters.sort_dir"
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
              placeholder="Search deleted integrations..."
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
          :meta="integrationsStore.meta"
          :loading="integrationsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </IntegrationTable>

    <DeleteConfirmation
      :open="Boolean(pendingForceDelete)"
      title="Permanently delete integration"
      :message="`Permanently delete ${pendingForceDelete?.name || 'this integration'}? This cannot be undone.`"
      confirm-label="Permanent Delete"
      :loading="integrationsStore.saving"
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
import IntegrationsHubSubnav from '@/modules/integrations/components/IntegrationsHubSubnav.vue';
import IntegrationTable from '@/modules/integrations/components/IntegrationTable.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const integrationsStore = useIntegrationsStore();
const search = ref('');
const pendingForceDelete = ref(null);
const previousFilters = ref(null);
let searchTimer = null;

onMounted(() => {
  previousFilters.value = { ...integrationsStore.filters };
  loadTrash();
});

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
  if (previousFilters.value) {
    integrationsStore.filters = {
      ...previousFilters.value,
      trashed: previousFilters.value.trashed === 'only' ? '' : previousFilters.value.trashed,
    };
  }
});

function loadTrash(overrides = {}) {
  return integrationsStore.fetchIntegrations({
    trashed: 'only',
    status: '',
    type: '',
    authentication_type: '',
    health_status: '',
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
  loadTrash({ page, sort_by: integrationsStore.filters.sort_by, sort_dir: integrationsStore.filters.sort_dir });
}

function onPerPageChange(perPage) {
  loadTrash({
    per_page: perPage,
    page: 1,
    sort_by: integrationsStore.filters.sort_by,
    sort_dir: integrationsStore.filters.sort_dir,
  });
}

function onSort(column) {
  const sortDir =
    integrationsStore.filters.sort_by === column && integrationsStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';
  loadTrash({ sort_by: column, sort_dir: sortDir, page: 1 });
}

async function confirmRestore(integration) {
  await integrationsStore.restoreIntegration(integration.uuid);
  await loadTrash({
    page: integrationsStore.filters.page,
    sort_by: integrationsStore.filters.sort_by,
    sort_dir: integrationsStore.filters.sort_dir,
  });
}

function openForceDelete(integration) {
  pendingForceDelete.value = integration;
}

async function confirmForceDelete() {
  if (!pendingForceDelete.value) {
    return;
  }

  await integrationsStore.forceDeleteIntegration(pendingForceDelete.value.uuid);
  pendingForceDelete.value = null;
  await loadTrash({
    page: integrationsStore.filters.page,
    sort_by: integrationsStore.filters.sort_by,
    sort_dir: integrationsStore.filters.sort_dir,
  });
}
</script>
