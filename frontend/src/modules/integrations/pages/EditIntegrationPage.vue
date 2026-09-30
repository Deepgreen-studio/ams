<template>
  <div>
    <div
      v-if="integrationsStore.loading && !integrationsStore.currentIntegration"
      class="mx-auto h-64 w-full max-w-[1200px] animate-pulse rounded-[12px] bg-slate-100"
    />
    <div v-else class="mx-auto w-full max-w-[1200px] rounded-[12px] bg-white p-6 sm:p-8">
      <IntegrationForm
        :initial="integrationsStore.currentIntegration || {}"
        :loading="integrationsStore.saving"
        :errors="integrationsStore.fieldErrors"
        :error="integrationsStore.error || ''"
        submit-label="Save Changes"
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
