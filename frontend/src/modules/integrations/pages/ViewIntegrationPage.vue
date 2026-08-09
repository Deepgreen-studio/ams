<template>
  <div>
    <!-- <PageHeader :title="integration?.name || 'Integration details'" description="Connection overview and configuration.">
      <template #actions>
        <template v-if="integration">
          <RouterLink :to="{ name: 'integrations.configuration', params: { id: integration.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Configure API
          </RouterLink>
          <RouterLink :to="{ name: 'integrations.connection', params: { id: integration.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Test connection
          </RouterLink>
          <RouterLink :to="{ name: 'integrations.edit', params: { id: integration.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Edit
          </RouterLink>
          <button
            v-if="integration.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="integrationsStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="integration">
          <RouterLink :to="{ name: 'integrations.configuration', params: { id: integration.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Configure API
          </RouterLink>
          <RouterLink :to="{ name: 'integrations.connection', params: { id: integration.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Test connection
          </RouterLink>
          <RouterLink :to="{ name: 'integrations.edit', params: { id: integration.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Edit
          </RouterLink>
          <button
            v-if="integration.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="integrationsStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
    </Teleport>

    <IntegrationSubnav v-if="integration" :integration-id="integration.uuid" />

    <div v-if="integrationsStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ integrationsStore.error }}
    </div>

    <div v-if="integrationsStore.loading && !integration" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <IntegrationCard v-else-if="integration" :integration="integration" />

    <DeleteConfirmation
      :open="showDelete"
      title="Delete integration"
      :message="`Soft delete ${integration?.name || 'this integration'}?`"
      confirm-label="Delete"
      :loading="integrationsStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import IntegrationCard from '@/modules/integrations/components/IntegrationCard.vue';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const router = useRouter();
const integrationsStore = useIntegrationsStore();
const showDelete = ref(false);

const integration = computed(() => integrationsStore.currentIntegration);

onMounted(() => {
  integrationsStore.fetchIntegration(route.params.id);
});

async function confirmDelete() {
  await integrationsStore.deleteIntegration(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'integrations.index' });
}

async function restore() {
  await integrationsStore.restoreIntegration(route.params.id);
  await integrationsStore.fetchIntegration(route.params.id);
}
</script>
