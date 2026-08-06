<template>
  <div>
    <PageHeader
      title="Edit integration"
      description="Update connection settings for this integration."
    />
    <div
      v-if="integrationsStore.loading && !integrationsStore.currentIntegration"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <IntegrationForm
        :initial="integrationsStore.currentIntegration || {}"
        :loading="integrationsStore.saving"
        :errors="integrationsStore.fieldErrors"
        :error="integrationsStore.error || ''"
        submit-label="Save changes"
        hide-company
        @submit="onSubmit"
        @cancel="router.push({ name: 'integrations.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import IntegrationForm from '@/modules/integrations/components/IntegrationForm.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const router = useRouter();
const integrationsStore = useIntegrationsStore();

onMounted(() => {
  integrationsStore.fetchIntegration(route.params.id);
});

async function onSubmit(payload) {
  const { company_id, ...updatePayload } = payload;
  await integrationsStore.updateIntegration(route.params.id, updatePayload);
  await router.push({ name: 'integrations.show', params: { id: route.params.id } });
}
</script>
