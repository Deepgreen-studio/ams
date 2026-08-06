<template>
  <div>
    <PageHeader
      title="AI Logs"
      description="Request-level usage logs across providers, features, and operations."
    />
    <AiSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search logs…"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All statuses</option>
        <option value="success">Success</option>
        <option value="failed">Failed</option>
      </select>
      <select v-model="filters.feature" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All features</option>
        <option v-for="feature in store.catalog.features || []" :key="feature.value" :value="feature.value">
          {{ feature.label }}
        </option>
      </select>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
          <tr>
            <th class="px-4 py-3">Feature</th>
            <th class="px-4 py-3">Operation</th>
            <th class="px-4 py-3">Driver</th>
            <th class="px-4 py-3">Tokens</th>
            <th class="px-4 py-3">Latency</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">When</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!store.logs.length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No AI logs yet.</td>
          </tr>
          <tr v-for="log in store.logs" :key="log.uuid" class="border-b border-slate-100">
            <td class="px-4 py-3">{{ log.feature_label || log.feature }}</td>
            <td class="px-4 py-3">{{ log.operation }}</td>
            <td class="px-4 py-3">{{ log.driver || 'n/a' }}</td>
            <td class="px-4 py-3">{{ log.tokens_in }}/{{ log.tokens_out }}</td>
            <td class="px-4 py-3">{{ log.latency_ms ?? '—' }} ms</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="log.status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
              >
                {{ log.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ formatDate(log.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const filters = reactive({ search: '', status: '', feature: '' });

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchLogs({ ...filters });
}

onMounted(async () => {
  await store.fetchCatalog();
  await load();
});
</script>
