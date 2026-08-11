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
      v-if="formError"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ formError }}
    </div>
    <div
      v-if="integrationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ integrationsStore.error }}
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <RequestTesterForm
          :loading="integrationsStore.saving"
          :error="integrationsStore.error || ''"
          @submit="onSubmit"
        />
      </div>
      <ResponseViewer :response="integrationsStore.lastResponse" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import RequestTesterForm from '@/modules/integrations/components/RequestTesterForm.vue';
import ResponseViewer from '@/modules/integrations/components/ResponseViewer.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const integrationsStore = useIntegrationsStore();
const formError = ref('');

onMounted(() => {
  integrationsStore.lastResponse = null;
  integrationsStore.fetchIntegration(route.params.id);
});

async function onSubmit(result) {
  formError.value = '';
  if (!result) {
    formError.value = 'Invalid JSON in headers, query, or body.';
    return;
  }
  await integrationsStore.executeRequest(route.params.id, result.payload, result.file);
}
</script>
