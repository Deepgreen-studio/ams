<template>
  <div>
    <!-- <PageHeader
      title="Create integration"
      description="Register a new external connection in the Integration Hub."
    /> -->
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <IntegrationForm
        :loading="integrationsStore.saving"
        :errors="integrationsStore.fieldErrors"
        :error="integrationsStore.error || ''"
        submit-label="Create integration"
        @submit="onSubmit"
        @cancel="router.push({ name: 'integrations.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import IntegrationForm from '@/modules/integrations/components/IntegrationForm.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const router = useRouter();
const integrationsStore = useIntegrationsStore();

async function onSubmit(payload) {
  const integration = await integrationsStore.createIntegration(payload);
  await router.push({ name: 'integrations.show', params: { id: integration.uuid } });
}
</script>
