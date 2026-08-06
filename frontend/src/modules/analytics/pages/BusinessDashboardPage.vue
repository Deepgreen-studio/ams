<template>
  <div>
    <PageHeader title="Business Dashboard" description="Portfolio growth, revenue, usage, support, and customer health.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="store.saving"
          @click="onCapture"
        >
          Capture snapshot
        </button>
      </template>
    </PageHeader>
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
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart title="Customer growth" :points="chartPoints(data.charts?.customer_growth)" value-key="value" stroke="#0f766e" fill="#0f766e" />
        <SimpleLineChart title="Revenue (MRR)" :points="chartPoints(data.charts?.revenue_trend)" value-key="value" stroke="#b45309" fill="#b45309" />
        <SimpleLineChart title="Subscription growth" :points="chartPoints(data.charts?.subscription_growth)" value-key="value" />
        <SimpleLineChart title="Application usage" :points="chartPoints(data.charts?.application_usage)" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
        <SimpleLineChart title="Support tickets" :points="chartPoints(data.charts?.support_tickets)" value-key="value" stroke="#be123c" fill="#be123c" />
        <SimpleLineChart title="Avg health score" :points="chartPoints(data.charts?.health_score)" value-key="value" stroke="#0369a1" fill="#0369a1" />
      </div>

      <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="MRR forecast"
          subtitle="Historical + projected"
          :points="forecastPoints(data.forecast?.mrr)"
          value-key="value"
          stroke="#b45309"
          fill="#b45309"
        />
        <SimpleLineChart
          title="Customer forecast"
          subtitle="Historical + projected"
          :points="forecastPoints(data.forecast?.customers_total)"
          value-key="value"
          stroke="#0f766e"
          fill="#0f766e"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import BusinessAnalyticsSubnav from '@/modules/analytics/components/BusinessAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';

const store = useBusinessAnalyticsStore();
const data = computed(() => store.overview);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const kpiCards = computed(() => [
  { label: 'Customers', value: data.value?.kpis?.customers_total ?? 0 },
  { label: 'Active customers', value: data.value?.kpis?.customers_active ?? 0 },
  { label: 'New customers', value: data.value?.kpis?.customers_new ?? 0 },
  { label: 'MRR', value: formatMoney(data.value?.kpis?.mrr) },
  { label: 'Active subscriptions', value: data.value?.kpis?.subscriptions_active ?? 0 },
  { label: 'Open tickets', value: data.value?.kpis?.support_tickets_open ?? 0 },
  { label: 'Avg health', value: data.value?.kpis?.avg_health_score ?? 0 },
  { label: 'At risk', value: data.value?.kpis?.at_risk_customers ?? 0 },
]);

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function chartPoints(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

function forecastPoints(block) {
  return chartPoints(block?.combined || []);
}

async function load() {
  await store.fetchOverview({ ...filters });
}

async function onCapture() {
  await store.capture();
  await load();
}

onMounted(load);
</script>
