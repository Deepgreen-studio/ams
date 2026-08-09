<template>
  <div>
    <!-- <PageHeader title="Growth & Forecast" description="Growth trends with linear forecast projections." /> -->
    <AnalyticsSubnav />
    <BusinessAnalyticsSubnav />

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
        Horizon
        <input v-model.number="filters.horizon_days" type="number" min="1" max="90" class="mt-1 block w-24 rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ store.error }}</div>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
      <SimpleLineChart title="Customer growth" :points="points(growth?.charts?.customer_growth)" value-key="value" stroke="#0f766e" fill="#0f766e" />
      <SimpleLineChart title="Subscription growth" :points="points(growth?.charts?.subscription_growth)" value-key="value" />
      <SimpleLineChart title="Revenue trend" :points="points(growth?.charts?.revenue_trend)" value-key="value" stroke="#b45309" fill="#b45309" />
      <SimpleLineChart title="Application usage" :points="points(growth?.charts?.application_usage)" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
    </div>

    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Forecast charts</h3>
    <div class="grid gap-4 lg:grid-cols-2">
      <SimpleLineChart title="MRR forecast" :points="points(forecast?.forecast?.mrr?.combined)" value-key="value" stroke="#b45309" fill="#b45309" />
      <SimpleLineChart title="Customer forecast" :points="points(forecast?.forecast?.customers_total?.combined)" value-key="value" stroke="#0f766e" fill="#0f766e" />
      <SimpleLineChart title="Subscription forecast" :points="points(forecast?.forecast?.subscriptions_active?.combined)" value-key="value" />
      <SimpleLineChart title="Sessions forecast" :points="points(forecast?.forecast?.application_sessions?.combined)" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import BusinessAnalyticsSubnav from '@/modules/analytics/components/BusinessAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';

const store = useBusinessAnalyticsStore();
const growth = computed(() => store.growth);
const forecast = computed(() => store.forecast);
const filters = reactive({
  from: new Date(Date.now() - 44 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
  horizon_days: 14,
});

function points(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

async function load() {
  await Promise.all([
    store.fetchGrowth({ from: filters.from, to: filters.to }),
    store.fetchForecast({ from: filters.from, to: filters.to, horizon_days: filters.horizon_days }),
  ]);
}

onMounted(load);
</script>
