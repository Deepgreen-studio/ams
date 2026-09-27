<template>
  <section class="mt-6 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-base font-semibold text-slate-900">Connector framework</h2>
        <p class="mt-1 text-sm text-slate-600">
          This integration uses the shared hub engines. Secrets stay in the credential vault.
        </p>
      </div>
      <button
        v-if="canRefresh"
        type="button"
        class="rounded-[12px] bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="refreshing"
        @click="refresh"
      >
        {{ refreshing ? 'Refreshing…' : 'Refresh OAuth token' }}
      </button>
    </div>

    <p v-if="error" class="mt-4 text-sm text-rose-700">{{ error }}</p>
    <p v-if="message" class="mt-4 text-sm text-emerald-700">{{ message }}</p>

    <div v-if="loading" class="mt-4 h-24 animate-pulse rounded-[12px] bg-slate-100" />

    <template v-else-if="profile">
      <div class="mt-4 flex flex-wrap gap-2">
        <span
          v-for="capability in profile.capabilities"
          :key="capability"
          class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700"
        >
          {{ capabilityLabel(capability) }}
        </span>
      </div>

      <ul class="mt-4 space-y-2">
        <li v-for="connector in profile.connectors" :key="connector.key" class="text-sm text-slate-700">
          <span class="font-medium text-slate-900">{{ connector.label }}.</span>
          {{ connector.description }}
        </li>
      </ul>

      <div class="mt-4 rounded-[12px] bg-zinc-50 px-4 py-3 text-sm text-slate-700">
        <p class="font-medium text-slate-900">Credentials</p>
        <p v-if="!profile.credentials?.configured" class="mt-1">No credentials stored.</p>
        <p v-else class="mt-1">
          Stored fields: {{ profile.credentials.keys.join(', ') }}
        </p>
        <p v-if="profile.credentials?.oauth_expires_at" class="mt-1">
          OAuth access token expires {{ profile.credentials.oauth_expires_at }}
        </p>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { integrationService } from '@/modules/integrations/services/integrationService';

const props = defineProps({
  integrationId: {
    type: String,
    required: true,
  },
});

const { can } = usePermissions();
const profile = ref(null);
const loading = ref(true);
const refreshing = ref(false);
const error = ref('');
const message = ref('');

const labels = {
  api: 'API',
  oauth: 'OAuth',
  webhook: 'Webhooks',
  api_key: 'API key',
  scheduled_sync: 'Scheduled sync',
  queue_retry: 'Queues and retries',
  monitoring: 'Monitoring',
  credentials: 'Credentials',
};

const canRefresh = computed(() => {
  return can('integrations.manage') && (profile.value?.capabilities ?? []).includes('oauth');
});

function capabilityLabel(capability) {
  return labels[capability] || capability;
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await integrationService.connector(props.integrationId);
    profile.value = data.data?.connector ?? null;
  } catch (err) {
    error.value = err?.message || 'Unable to load the connector profile.';
  } finally {
    loading.value = false;
  }
}

async function refresh() {
  refreshing.value = true;
  error.value = '';
  message.value = '';
  try {
    const { data } = await integrationService.refreshOAuth(props.integrationId);
    profile.value = data.data?.connector ?? profile.value;
    message.value = data.message || 'OAuth token refreshed.';
  } catch (err) {
    error.value = err?.message || 'Unable to refresh the OAuth token.';
  } finally {
    refreshing.value = false;
  }
}

onMounted(load);
watch(() => props.integrationId, load);
</script>
