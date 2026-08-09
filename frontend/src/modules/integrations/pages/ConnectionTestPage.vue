<template>
  <div>
    <!-- <PageHeader
      title="Connection Test"
      description="Validate reachability and authentication through the API Connection Engine."
    /> -->
    <IntegrationSubnav v-if="route.params.id" :integration-id="route.params.id" />

    <div
      v-if="integrationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ integrationsStore.successMessage }}
    </div>
    <div
      v-if="integrationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ integrationsStore.error }}
    </div>

    <div
      v-if="integrationsStore.loading && !integration"
      class="h-40 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="integration" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Base URL</dt>
            <dd class="mt-1 break-all text-sm text-slate-900">{{ integration.base_url || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Health path</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ integration.health_check_path || '/' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Health status</dt>
            <dd class="mt-1"><StatusBadge :status="integration.health_status" kind="health" /></dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Credentials</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ integration.has_credentials ? 'Configured' : 'Missing' }}
            </dd>
          </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="integrationsStore.saving || !integration.base_url"
            @click="runConnection"
          >
            {{ integrationsStore.saving ? 'Testing...' : 'Run connection test' }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
            :disabled="integrationsStore.saving || !integration.has_credentials"
            @click="runAuth"
          >
            Run authentication test
          </button>
        </div>
      </div>

      <ResponseViewer :response="integrationsStore.lastResponse" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import ResponseViewer from '@/modules/integrations/components/ResponseViewer.vue';
import StatusBadge from '@/modules/integrations/components/StatusBadge.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const integrationsStore = useIntegrationsStore();
const integration = computed(() => integrationsStore.currentIntegration);

onMounted(() => {
  integrationsStore.lastResponse = null;
  integrationsStore.fetchIntegration(route.params.id);
});

async function runConnection() {
  await integrationsStore.testConnection(route.params.id);
}

async function runAuth() {
  await integrationsStore.testAuthentication(route.params.id);
}
</script>
