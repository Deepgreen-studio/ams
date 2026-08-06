<template>
  <div>
    <PageHeader :title="pageTitle" :description="pageDescription">
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
      <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ data.focus?.headline }}</p>
        <p class="mt-1 text-sm text-slate-600">{{ data.focus?.summary }}</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">Business score {{ data.kpis?.business_score ?? 0 }}</p>
      </div>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in data.scorecards || []" :key="card.key" class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium text-slate-800">{{ card.label }}</p>
            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusClass(card.status)">
              {{ card.status }}
            </span>
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatScorecardValue(card) }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ card.unit_label }} · score {{ card.score }}</p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          v-for="chart in chartCards"
          :key="chart.title"
          :title="chart.title"
          :points="chart.points"
          value-key="value"
          :stroke="chart.stroke"
          :fill="chart.fill"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div v-if="data.widgets?.top_customers" class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Top customers</h3>
          <ul class="mt-3 divide-y divide-slate-100">
            <li v-for="row in data.widgets.top_customers" :key="row.customer_uuid || row.email" class="flex justify-between gap-3 py-2 text-sm">
              <span class="truncate text-slate-700">{{ row.display_name || row.email }}</span>
              <span class="shrink-0 font-medium text-slate-900">{{ formatMoney(row.revenue) }}</span>
            </li>
            <li v-if="!data.widgets.top_customers.length" class="py-6 text-center text-sm text-slate-500">No customers yet.</li>
          </ul>
        </div>

        <div v-if="data.widgets?.top_applications" class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Top applications</h3>
          <ul class="mt-3 divide-y divide-slate-100">
            <li v-for="row in data.widgets.top_applications" :key="row.application_uuid || row.name" class="flex justify-between gap-3 py-2 text-sm">
              <span class="truncate text-slate-700">{{ row.name }}</span>
              <span class="shrink-0 text-slate-500">{{ row.sessions }} sessions</span>
            </li>
            <li v-if="!data.widgets.top_applications.length" class="py-6 text-center text-sm text-slate-500">No application usage yet.</li>
          </ul>
        </div>

        <div v-if="data.widgets?.support_sla" class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Support SLA</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><p class="text-slate-500">On track</p><p class="text-lg font-semibold">{{ data.widgets.support_sla.on_track }}</p></div>
            <div><p class="text-slate-500">At risk</p><p class="text-lg font-semibold">{{ data.widgets.support_sla.at_risk }}</p></div>
            <div><p class="text-slate-500">Breached</p><p class="text-lg font-semibold">{{ data.widgets.support_sla.breached }}</p></div>
            <div><p class="text-slate-500">Met</p><p class="text-lg font-semibold">{{ data.widgets.support_sla.met }}</p></div>
          </div>
        </div>

        <div v-if="data.widgets?.compliance_status" class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Compliance status</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><p class="text-slate-500">Cases open</p><p class="text-lg font-semibold">{{ data.widgets.compliance_status.cases_open }}</p></div>
            <div><p class="text-slate-500">Risk score</p><p class="text-lg font-semibold">{{ data.widgets.compliance_status.risk_score }}</p></div>
            <div><p class="text-slate-500">Privacy open</p><p class="text-lg font-semibold">{{ data.widgets.compliance_status.privacy_open }}</p></div>
            <div><p class="text-slate-500">Breaches open</p><p class="text-lg font-semibold">{{ data.widgets.compliance_status.breaches_open }}</p></div>
          </div>
        </div>

        <div v-if="data.widgets?.system_health" class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">System health</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><p class="text-slate-500">Health</p><p class="text-lg font-semibold">{{ data.widgets.system_health.health_score }}</p></div>
            <div><p class="text-slate-500">Uptime %</p><p class="text-lg font-semibold">{{ data.widgets.system_health.uptime_percent }}</p></div>
            <div><p class="text-slate-500">Error rate</p><p class="text-lg font-semibold">{{ data.widgets.system_health.error_rate }}</p></div>
          </div>
        </div>

        <div v-if="data.widgets?.revenue" class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Revenue</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><p class="text-slate-500">MRR</p><p class="text-lg font-semibold">{{ formatMoney(data.widgets.revenue.mrr) }}</p></div>
            <div><p class="text-slate-500">Period</p><p class="text-lg font-semibold">{{ formatMoney(data.widgets.revenue.revenue_period) }}</p></div>
            <div><p class="text-slate-500">Active subs</p><p class="text-lg font-semibold">{{ data.widgets.revenue.subscriptions_active }}</p></div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import ExecutiveAnalyticsSubnav from '@/modules/analytics/components/ExecutiveAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';

const props = defineProps({
  dashboardType: { type: String, default: 'ceo' },
});

const route = useRoute();
const store = useExecutiveAnalyticsStore();
const data = computed(() => store.dashboard);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const type = computed(() => props.dashboardType || route.meta?.executiveType || 'ceo');

const pageTitle = computed(() => data.value?.label || 'Executive Dashboard');
const pageDescription = computed(() => data.value?.focus?.summary || 'Leadership KPIs, scorecards, and portfolio widgets.');

const kpiCards = computed(() => [
  { label: 'MRR', value: formatMoney(data.value?.kpis?.mrr) },
  { label: 'Active customers', value: data.value?.kpis?.customers_active ?? 0 },
  { label: 'Applications', value: data.value?.kpis?.applications_total ?? 0 },
  { label: 'SLA breached', value: data.value?.kpis?.support_sla_breached ?? 0 },
  { label: 'Compliance risk', value: data.value?.kpis?.compliance_risk_score ?? 0 },
  { label: 'System health', value: data.value?.kpis?.system_health_score ?? 0 },
  { label: 'Security risk', value: data.value?.kpis?.security_risk_score ?? 0 },
  { label: 'Uptime %', value: data.value?.kpis?.system_uptime_percent ?? 0 },
]);

const chartCards = computed(() => {
  const charts = data.value?.charts || {};
  const colors = {
    revenue_trend: ['#b45309', '#b45309'],
    customer_growth: ['#0f766e', '#0f766e'],
    health_score: ['#0369a1', '#0369a1'],
    application_usage: ['#7c3aed', '#7c3aed'],
    support_tickets: ['#be123c', '#be123c'],
    system_health: ['#0f766e', '#0f766e'],
  };
  return Object.entries(charts).map(([key, series]) => ({
    title: key.replaceAll('_', ' '),
    points: chartPoints(series, key === 'system_health' ? 'health_score' : 'value'),
    stroke: colors[key]?.[0] || '#0f172a',
    fill: colors[key]?.[1] || '#0f172a',
  }));
});

function chartPoints(series = [], valueKey = 'value') {
  return (series || []).map((row) => ({
    ...row,
    label: row.date || row.bucket || row.label,
    value: row[valueKey] ?? row.value ?? 0,
  }));
}

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function formatScorecardValue(card) {
  if (card.key === 'revenue') return formatMoney(card.value);
  return card.value;
}

function statusClass(status) {
  const map = {
    excellent: 'bg-emerald-50 text-emerald-700',
    good: 'bg-sky-50 text-sky-700',
    watch: 'bg-amber-50 text-amber-700',
    critical: 'bg-rose-50 text-rose-700',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

async function load() {
  await store.fetchDashboard(type.value, { ...filters });
}

async function onCapture() {
  await store.capture();
  await load();
}

watch(type, load);
onMounted(load);
</script>
