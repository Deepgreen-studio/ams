<template>
  <div>
    <PageHeader title="Integrations" description="Manage external system connections for the Integration Hub.">
      <template #actions>
        <RouterLink :to="{ name: 'integrations.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
          Create integration
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="integrationsStore.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ integrationsStore.successMessage }}
    </div>
    <div v-if="integrationsStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ integrationsStore.error }}
    </div>

    <div class="space-y-4">
      <SearchFilters :model-value="integrationsStore.filters" @submit="onFilter" @reset="onReset" />

      <IntegrationTable :integrations="integrationsStore.integrations" :loading="integrationsStore.loading" @delete="openDelete">
        <template #empty-action>
          <RouterLink :to="{ name: 'integrations.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
            Create integration
          </RouterLink>
        </template>
      </IntegrationTable>

      <Pagination :meta="integrationsStore.meta" :loading="integrationsStore.loading" @change="onPageChange" />
    </div>

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
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import IntegrationTable from '@/modules/integrations/components/IntegrationTable.vue';
import SearchFilters from '@/modules/integrations/components/SearchFilters.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const integrationsStore = useIntegrationsStore();
const pendingDelete = ref(null);

onMounted(() => {
  integrationsStore.fetchIntegrations();
});

function onFilter(filters) {
  integrationsStore.fetchIntegrations(filters);
}

function onReset() {
  integrationsStore.filters = {
    search: '',
    status: '',
    type: '',
    authentication_type: '',
    health_status: '',
    company: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  };
  integrationsStore.fetchIntegrations();
}

function onPageChange(page) {
  integrationsStore.fetchIntegrations({ page });
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
