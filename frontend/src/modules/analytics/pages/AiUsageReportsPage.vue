<template>
  <div>
    <PageHeader
      title="AI Usage Analytics"
      description="Cross-domain AI request volume, tokens, latency, and driver breakdown."
    />
    <AnalyticsSubnav />
    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'ai')"
    />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.ai" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.ai">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4">
        <SimpleLineChart
          title="AI requests"
          :labels="store.ai.trends?.labels || []"
          :series="[{ key: 'requests', label: 'Requests', values: store.ai.trends?.requests || [] }]"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">By feature</h2>
          <ul class="divide-y divide-slate-100">
            <li v-if="!(store.ai.by_feature || []).length" class="py-6 text-center text-sm text-slate-500">No usage.</li>
            <li
              v-for="row in store.ai.by_feature || []"
              :key="row.feature"
              class="flex items-center justify-between py-3 text-sm"
            >
              <span class="text-slate-700">{{ row.feature }}</span>
              <span class="font-medium text-slate-900">{{ row.total }} · {{ row.tokens }} tok</span>
            </li>
          </ul>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">By driver</h2>
          <ul class="divide-y divide-slate-100">
            <li v-if="!(store.ai.by_driver || []).length" class="py-6 text-center text-sm text-slate-500">No usage.</li>
            <li
              v-for="row in store.ai.by_driver || []"
              :key="row.driver || 'none'"
              class="flex items-center justify-between py-3 text-sm"
            >
              <span class="text-slate-700">{{ row.driver || 'n/a' }}</span>
              <span class="font-medium text-slate-900">{{ row.total }} · {{ row.tokens }} tok</span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();

const cards = computed(() => [
  { label: 'Requests', value: store.ai?.requests ?? 0 },
  { label: 'Tokens in', value: store.ai?.tokens_in ?? 0 },
  { label: 'Tokens out', value: store.ai?.tokens_out ?? 0 },
  { label: 'Avg latency (ms)', value: store.ai?.avg_latency_ms ?? 0 },
  { label: 'Success', value: store.ai?.success_count ?? 0 },
  { label: 'Failed', value: store.ai?.failed_count ?? 0 },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchAi();
}

onMounted(() => store.fetchAi());
</script>
