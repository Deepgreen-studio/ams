<template>
  <div>
    <PageHeader
      title="Workflow Reports"
      description="Workflow success rate, failures, and average processing time."
    />
    <AnalyticsSubnav />
    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'workflows')"
    />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.workflows" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.workflows">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Workflow trends"
          :labels="store.workflows.trends?.labels || []"
          :series="trendSeries"
        />
        <SimpleBarChart title="By status" :data="store.workflows.by_status || {}" />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Top workflows</h2>
        <ul class="divide-y divide-slate-100">
          <li v-if="!(store.workflows.top_workflows || []).length" class="py-6 text-center text-sm text-slate-500">No instances.</li>
          <li
            v-for="row in store.workflows.top_workflows || []"
            :key="row.workflow_id"
            class="flex items-center justify-between gap-3 py-3 text-sm"
          >
            <div>
              <p class="font-medium text-slate-900">{{ row.workflow_name }}</p>
              <p class="text-xs text-slate-500">{{ row.success_rate }}% success</p>
            </div>
            <span class="font-medium text-slate-900">{{ row.total }}</span>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();

const cards = computed(() => [
  { label: 'Instances', value: store.workflows?.total ?? 0 },
  { label: 'Success', value: store.workflows?.success ?? 0 },
  { label: 'Failures', value: store.workflows?.failures ?? 0 },
  { label: 'Success rate', value: `${store.workflows?.success_rate ?? 0}%` },
  { label: 'Avg processing (s)', value: store.workflows?.avg_processing_seconds ?? 0 },
  { label: 'In progress', value: store.workflows?.in_progress ?? 0 },
]);

const trendSeries = computed(() => [
  { key: 'created', label: 'Created', values: store.workflows?.trends?.created || [] },
  { key: 'completed', label: 'Completed', values: store.workflows?.trends?.completed || [] },
  { key: 'failed', label: 'Failed', values: store.workflows?.trends?.failed || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchWorkflows();
}

onMounted(() => store.fetchWorkflows());
</script>
