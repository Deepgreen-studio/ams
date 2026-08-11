<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
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
import IntegrationForm from '@/modules/integrations/components/IntegrationForm.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const router = useRouter();
const integrationsStore = useIntegrationsStore();

async function onSubmit(payload) {
  const integration = await integrationsStore.createIntegration(payload);
  await router.push({ name: 'integrations.show', params: { id: integration.uuid } });
}
</script>
