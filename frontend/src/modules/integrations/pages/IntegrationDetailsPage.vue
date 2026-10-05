<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="integration" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-if="can('integrations.update') && !integration.deleted_at"
          :to="{ name: 'integrations.edit', params: { id: integration.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
        <button
          v-if="integration.deleted_at && can('integrations.restore')"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="integrationsStore.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-if="integration.deleted_at && can('integrations.force-delete')"
          type="button"
          class="rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60"
          :disabled="integrationsStore.saving"
          @click="showForceDelete = true"
        >
          Permanent Delete
        </button>
        <button
          v-else-if="!integration.deleted_at && can('integrations.delete')"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Soft Delete
        </button>
      </div>
    </Teleport>

    <IntegrationSubnav v-if="integration" :integration-id="integration.uuid" />

    <div
      v-if="integrationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ integrationsStore.error }}
    </div>

    <div
      v-if="integrationsStore.loading && !integration"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />
    <template v-else-if="integration">
      <IntegrationCard :integration="integration" />
      <ConnectorPanel :integration-id="integration.uuid" />
    </template>

    <DeleteConfirmation
      :open="showDelete"
      title="Soft delete integration"
      :message="`Soft delete ${integration?.name || 'this integration'}? It can be restored later.`"
      confirm-label="Soft Delete"
      :loading="integrationsStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />

    <DeleteConfirmation
      :open="showForceDelete"
      title="Permanently delete integration"
      :message="`Permanently delete ${integration?.name || 'this integration'}? This cannot be undone.`"
      confirm-label="Permanent Delete"
      :loading="integrationsStore.saving"
      @cancel="showForceDelete = false"
      @confirm="confirmForceDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ConnectorPanel from '@/modules/integrations/components/ConnectorPanel.vue';
import IntegrationCard from '@/modules/integrations/components/IntegrationCard.vue';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const router = useRouter();
const integrationsStore = useIntegrationsStore();
const { can } = usePermissions();
const showDelete = ref(false);
const showForceDelete = ref(false);

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

async function confirmForceDelete() {
  await integrationsStore.forceDeleteIntegration(route.params.id);
  showForceDelete.value = false;
  await router.push({ name: 'integrations.trash' });
}
</script>
