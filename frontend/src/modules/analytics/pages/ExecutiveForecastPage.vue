<template>
  <div>
    <!-- <PageHeader title="Executive Forecast" description="Forward-looking revenue and customer growth projections." /> -->
    <AnalyticsSubnav />
    <ExecutiveAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        From
        <input v-model="filters.from" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        To
        <input v-model="filters.to" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        Horizon (days)
        <input v-model.number="filters.horizon_days" type="number" min="1" max="90" class="mt-1 block w-28 rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="MRR forecast"
          subtitle="Historical + projected"
          :points="forecastPoints(data.forecast?.mrr || data.forecast?.forecast?.mrr)"
          value-key="value"
          stroke="#b45309"
          fill="#b45309"
        />
        <SimpleLineChart
          title="Customer forecast"
          subtitle="Historical + projected"
          :points="forecastPoints(data.forecast?.customers_total || data.forecast?.forecast?.customers_total)"
          value-key="value"
          stroke="#0f766e"
          fill="#0f766e"
        />
        <SimpleLineChart
          title="Customer growth"
          :points="chartPoints(data.growth?.charts?.customer_growth)"
          value-key="value"
          stroke="#0369a1"
          fill="#0369a1"
        />
        <SimpleLineChart
          title="Revenue trend"
          :points="chartPoints(data.growth?.charts?.revenue_trend)"
          value-key="value"
          stroke="#7c3aed"
          fill="#7c3aed"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import ExecutiveAnalyticsSubnav from '@/modules/analytics/components/ExecutiveAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';

const store = useExecutiveAnalyticsStore();
const data = computed(() => store.forecast);

const filters = reactive({
  from: new Date(Date.now() - 44 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
  horizon_days: 14,
});

function chartPoints(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date || row.label }));
}

function forecastPoints(block) {
  return chartPoints(block?.combined || block || []);
}

async function load() {
  await store.fetchForecast({ ...filters });
}

onMounted(load);
</script>
