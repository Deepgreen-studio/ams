<template>
  <div>
    <!-- <PageHeader
      title="API Configuration"
      description="Headers, credentials, timeout, retries, and rate limits for this integration."
    /> -->
    <IntegrationSubnav v-if="route.params.id" :integration-id="route.params.id" />

    <div
      v-if="integrationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ integrationsStore.successMessage }}
    </div>

    <div
      v-if="integrationsStore.loading && !integrationsStore.currentIntegration"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
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
// import PageHeader from '@/components/ui/PageHeader.vue';
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
