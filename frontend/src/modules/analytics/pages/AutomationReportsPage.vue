<template>
  <div>
    <!-- <PageHeader
      title="Automation Reports"
      description="Automation executions, success rate, and average processing time."
    /> -->
    <AnalyticsSubnav />
    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'automation')"
    />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.automation" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.automation">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Execution trends"
          :labels="store.automation.trends?.labels || []"
          :series="trendSeries"
        />
        <SimpleBarChart title="By trigger type" :data="store.automation.by_trigger || {}" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By status" :data="store.automation.by_status || {}" />
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Top rules</h2>
          <ul class="divide-y divide-slate-100">
            <li v-if="!(store.automation.top_rules || []).length" class="py-6 text-center text-sm text-slate-500">No executions.</li>
            <li
              v-for="row in store.automation.top_rules || []"
              :key="row.rule_id"
              class="flex items-center justify-between gap-3 py-3 text-sm"
            >
              <div>
                <p class="font-medium text-slate-900">{{ row.rule_name }}</p>
                <p class="text-xs text-slate-500">{{ row.success_rate }}% success</p>
              </div>
              <span class="font-medium text-slate-900">{{ row.total }}</span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();

const cards = computed(() => [
  { label: 'Executions', value: store.automation?.total ?? 0 },
  { label: 'Success', value: store.automation?.success ?? 0 },
  { label: 'Failed', value: store.automation?.failed ?? 0 },
  { label: 'Success rate', value: `${store.automation?.success_rate ?? 0}%` },
  { label: 'Avg processing (s)', value: store.automation?.avg_processing_seconds ?? 0 },
  { label: 'Skipped', value: store.automation?.skipped ?? 0 },
]);

const trendSeries = computed(() => [
  { key: 'executions', label: 'Total', values: store.automation?.trends?.executions || [] },
  { key: 'success', label: 'Success', values: store.automation?.trends?.success || [] },
  { key: 'failed', label: 'Failed', values: store.automation?.trends?.failed || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchAutomation();
}

onMounted(() => store.fetchAutomation());
</script>
