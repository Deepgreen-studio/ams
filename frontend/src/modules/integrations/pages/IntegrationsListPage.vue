<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="can('integrations.create')"
        :to="{ name: 'integrations.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create integration
      </RouterLink>
    </Teleport>

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
      @delete="openDelete"
    >
      <template #toolbar>
        <SearchFilters
          :model-value="integrationsStore.filters"
          @submit="onFilter"
          @reset="onReset"
        />
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
          v-if="can('integrations.create')"
          :to="{ name: 'integrations.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create integration
        </RouterLink>
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
      :open="Boolean(pendingDelete)"
      title="Delete integration"
      :message="`Soft delete ${pendingDelete?.name || 'this integration'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="integrationsStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import IntegrationTable from '@/modules/integrations/components/IntegrationTable.vue';
import SearchFilters from '@/modules/integrations/components/SearchFilters.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const integrationsStore = useIntegrationsStore();
const { can } = usePermissions();
const pendingDelete = ref(null);

onMounted(() => {
  integrationsStore.fetchIntegrations();
});

function onFilter(filters) {
  integrationsStore.fetchIntegrations(filters);
}

function onReset() {
  integrationsStore.resetFilters();
  integrationsStore.fetchIntegrations();
}

function onPageChange(page) {
  integrationsStore.fetchIntegrations({ page });
}

function onPerPageChange(perPage) {
  integrationsStore.fetchIntegrations({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    integrationsStore.filters.sort_by === column && integrationsStore.filters.sort_dir === 'asc'
      ? 'desc'
      : 'asc';

  integrationsStore.fetchIntegrations({ sort_by: column, sort_dir: sortDir, page: 1 });
}

function openDelete(integration) {
  pendingDelete.value = integration;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await integrationsStore.deleteIntegration(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await integrationsStore.fetchIntegrations();
}
</script>
