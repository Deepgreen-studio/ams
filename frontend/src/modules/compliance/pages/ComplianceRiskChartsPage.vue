<template>
  <div>
    <PageHeader
      title="Risk charts"
      description="Open vs closed risks, score distribution, and highest-scoring active risks."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.analytics.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Analytics dashboard
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'risks')"
    />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.risks" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.risks">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white px-4 py-3"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Risk intake vs closures"
          :labels="store.risks.trends?.labels || []"
          :series="trendSeries"
        />
        <SimpleBarChart title="By risk level" :data="store.risks.by_level || {}" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By status" :data="store.risks.by_status || {}" />
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Top open risks</h3>
          <EmptyState
            v-if="!(store.risks.top_risks || []).length"
            title="No active risks"
            description="High-scoring risks appear here."
          />
          <ul v-else class="divide-y divide-slate-100 text-sm">
            <li
              v-for="item in store.risks.top_risks"
              :key="item.uuid"
              class="flex items-center justify-between gap-3 py-3"
            >
              <div class="min-w-0">
                <p class="truncate font-medium text-slate-900">{{ item.title }}</p>
                <p class="text-xs text-slate-500">{{ item.risk_number }} · {{ item.status }}</p>
              </div>
              <span class="shrink-0 font-semibold text-slate-900">{{ item.risk_score }}</span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();

const summaryCards = computed(() => {
  const s = store.risks?.summary || {};
  return [
    { label: 'Open risks', value: s.open ?? 0 },
    { label: 'Closed risks', value: s.closed ?? 0 },
    { label: 'Average score', value: s.average_score ?? 0 },
    { label: 'Created in period', value: s.created_in_period ?? 0 },
  ];
});

const trendSeries = computed(() => [
  { key: 'opened', label: 'Opened', values: store.risks?.trends?.opened || [] },
  { key: 'closed', label: 'Closed', values: store.risks?.trends?.closed || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchRisks();
}

onMounted(() => store.fetchRisks());
</script>
