<template>
  <div>
    <IntegrationSubnav v-if="route.params.id" :integration-id="route.params.id" />

    <div
      v-if="integrationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ integrationsStore.successMessage }}
    </div>

    <div
      v-if="integrationsStore.loading && !integrationsStore.currentIntegration"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />
    <div v-else class="rounded-[12px] bg-white p-6 sm:p-8">
      <ApiConfigurationForm
        :initial="integrationsStore.currentIntegration || {}"
        :loading="integrationsStore.saving"
        :error="integrationsStore.error || ''"
        @submit="onSubmit"
        @cancel="router.push({ name: 'integrations.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ApiConfigurationForm from '@/modules/integrations/components/ApiConfigurationForm.vue';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const router = useRouter();
const integrationsStore = useIntegrationsStore();

onMounted(() => {
  integrationsStore.fetchIntegration(route.params.id);
});

async function onSubmit(payload) {
  await integrationsStore.updateConfiguration(route.params.id, payload);
}
</script>
