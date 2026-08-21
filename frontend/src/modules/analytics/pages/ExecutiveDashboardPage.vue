<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.executive.scorecards' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Scorecards
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.loading"
        @click="load"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.loading }" />
        Refresh
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="onCapture"
      >
        <CameraIcon class="h-4 w-4" :class="{ 'animate-pulse': store.saving }" />
        {{ store.saving ? 'Capturing…' : 'Capture snapshot' }}
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      :model-value="filters"
      :show-category="false"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="Unable to load executive dashboard"
        :description="store.error || 'Refresh to try loading leadership KPIs again.'"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="load"
          >
            Retry
          </button>
        </template>
      </EmptyState>
    </div>

    <template v-else-if="data">
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <div class="min-w-0">
          <p class="font-medium">{{ data.focus?.headline || 'Portfolio health' }}</p>
          <p class="mt-0.5">{{ healthMessage }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in kpiCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in data.scorecards || []"
          :key="card.key"
          class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100"
        >
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium text-slate-800">{{ card.label }}</p>
            <span
              class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
              :class="statusClass(card.status)"
            >
              {{ card.status }}
            </span>
          </div>
          <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ formatScorecardValue(card) }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ card.unit_label }} · score {{ card.score }}</p>
        </div>
      </div>

      <div v-if="chartCards.length" class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          v-for="chart in chartCards"
          :key="chart.title"
          :title="chart.title"
          :hint="chart.hint"
          :labels="chart.labels"
          :series="chart.series"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section v-if="data.widgets?.top_customers" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Top customers</h2>
            <p class="mt-0.5 text-xs text-slate-500">Highest revenue contribution in this period.</p>
          </div>
          <div v-if="!data.widgets.top_customers.length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No customers yet</p>
            <p class="mt-1 text-xs text-slate-500">Customer revenue will appear here after the next snapshot.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="row in data.widgets.top_customers"
              :key="row.customer_uuid || row.email"
              class="flex items-center justify-between gap-3 px-3 py-3"
            >
              <span class="truncate text-sm font-medium text-slate-900">{{ row.display_name || row.email }}</span>
              <span class="shrink-0 text-sm font-semibold text-slate-900">{{ formatMoney(row.revenue) }}</span>
            </li>
          </ul>
        </section>

        <section v-if="data.widgets?.top_applications" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Top applications</h2>
            <p class="mt-0.5 text-xs text-slate-500">Session volume across the portfolio.</p>
          </div>
          <div v-if="!data.widgets.top_applications.length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No application usage yet</p>
            <p class="mt-1 text-xs text-slate-500">Session counts will appear once apps report activity.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="row in data.widgets.top_applications"
              :key="row.application_uuid || row.name"
              class="flex items-center justify-between gap-3 px-3 py-3"
            >
              <span class="truncate text-sm font-medium text-slate-900">{{ row.name }}</span>
              <span class="shrink-0 text-sm text-slate-500">{{ row.sessions }} sessions</span>
            </li>
          </ul>
        </section>

        <section v-if="data.widgets?.support_sla" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Support SLA</h2>
            <p class="mt-0.5 text-xs text-slate-500">Timer posture for tracked tickets.</p>
          </div>
          <dl class="divide-y divide-zinc-50 px-6 py-2">
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">On track</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.support_sla.on_track }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">At risk</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.support_sla.at_risk }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Breached</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.support_sla.breached }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Met</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.support_sla.met }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="data.widgets?.compliance_status" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Compliance status</h2>
            <p class="mt-0.5 text-xs text-slate-500">Open cases, privacy, and breach exposure.</p>
          </div>
          <dl class="divide-y divide-zinc-50 px-6 py-2">
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Cases open</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.compliance_status.cases_open }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Risk score</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.compliance_status.risk_score }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Privacy open</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.compliance_status.privacy_open }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Breaches open</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.compliance_status.breaches_open }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="data.widgets?.system_health" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">System health</h2>
            <p class="mt-0.5 text-xs text-slate-500">Platform reliability for the selected period.</p>
          </div>
          <dl class="divide-y divide-zinc-50 px-6 py-2">
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Health</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.system_health.health_score }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Uptime %</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.system_health.uptime_percent }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Error rate</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.system_health.error_rate }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="data.widgets?.revenue" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Revenue</h2>
            <p class="mt-0.5 text-xs text-slate-500">Subscription and period contribution.</p>
          </div>
          <dl class="divide-y divide-zinc-50 px-6 py-2">
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">MRR</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ formatMoney(data.widgets.revenue.mrr) }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Period</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ formatMoney(data.widgets.revenue.revenue_period) }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Active subs</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.revenue.subscriptions_active }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="data.widgets?.growth_metrics" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Growth</h2>
            <p class="mt-0.5 text-xs text-slate-500">New and active customers in this period.</p>
          </div>
          <dl class="divide-y divide-zinc-50 px-6 py-2">
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">New customers</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.growth_metrics.customers_new }}</dd>
            </div>
            <div class="flex items-center justify-between py-3">
              <dt class="text-sm text-slate-500">Active customers</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ data.widgets.growth_metrics.customers_active }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowPathIcon,
  CameraIcon,
  ChartBarIcon,
  CheckCircleIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  HeartIcon,
  ServerStackIcon,
  ShieldExclamationIcon,
  Squares2X2Icon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';

const props = defineProps({
  dashboardType: { type: String, default: 'ceo' },
});

const route = useRoute();
const store = useExecutiveAnalyticsStore();
const toast = useToast();
const data = computed(() => store.dashboard);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const type = computed(() => props.dashboardType || route.meta?.executiveType || 'ceo');

const kpis = computed(() => data.value?.kpis || {});
const scorecards = computed(() => data.value?.scorecards || []);
const businessScore = computed(() => Number(kpis.value.business_score ?? 0));
const criticalCount = computed(() => scorecards.value.filter((card) => card.status === 'critical').length);
const watchCount = computed(() => scorecards.value.filter((card) => card.status === 'watch').length);

const kpiCards = computed(() => {
  const k = kpis.value;
  const mrr = Number(k.mrr || 0);
  const customers = Number(k.customers_active ?? 0);
  const applications = Number(k.applications_total ?? 0);
  const slaBreached = Number(k.support_sla_breached ?? 0);
  const complianceRisk = Number(k.compliance_risk_score ?? 0);
  const systemHealth = Number(k.system_health_score ?? 0);
  const securityRisk = Number(k.security_risk_score ?? 0);
  const uptime = Number(k.system_uptime_percent ?? 0);

  return [
    {
      label: 'MRR',
      value: formatMoney(mrr),
      hint: 'Monthly recurring revenue',
      icon: CurrencyDollarIcon,
      iconBg: mrr ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: mrr ? 'text-amber-600' : 'text-slate-500',
    },
    {
      label: 'Active customers',
      value: customers,
      hint: 'Customers with activity',
      icon: UserGroupIcon,
      iconBg: customers ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: customers ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Applications',
      value: applications,
      hint: 'In the portfolio',
      icon: Squares2X2Icon,
      iconBg: applications ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: applications ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'SLA breached',
      value: slaBreached,
      hint: slaBreached ? 'Needs immediate attention' : 'No active breaches',
      icon: ExclamationTriangleIcon,
      iconBg: slaBreached ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: slaBreached ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Compliance risk',
      value: complianceRisk,
      hint: 'Composite risk score',
      icon: ShieldExclamationIcon,
      iconBg: complianceRisk >= 40 ? 'bg-rose-50' : complianceRisk ? 'bg-amber-50' : 'bg-emerald-50',
      iconColor: complianceRisk >= 40 ? 'text-rose-500' : complianceRisk ? 'text-amber-500' : 'text-emerald-500',
    },
    {
      label: 'System health',
      value: systemHealth,
      hint: 'Platform reliability score',
      icon: ServerStackIcon,
      iconBg: systemHealth >= 80 ? 'bg-emerald-50' : systemHealth ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: systemHealth >= 80 ? 'text-emerald-500' : systemHealth ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Security risk',
      value: securityRisk,
      hint: 'Threat and audit exposure',
      icon: ShieldExclamationIcon,
      iconBg: securityRisk >= 40 ? 'bg-rose-50' : securityRisk ? 'bg-amber-50' : 'bg-emerald-50',
      iconColor: securityRisk >= 40 ? 'text-rose-500' : securityRisk ? 'text-amber-500' : 'text-emerald-500',
    },
    {
      label: 'Uptime %',
      value: uptime,
      hint: 'Reported availability',
      icon: HeartIcon,
      iconBg: uptime >= 99 ? 'bg-emerald-50' : uptime ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: uptime >= 99 ? 'text-emerald-500' : uptime ? 'text-amber-500' : 'text-slate-500',
    },
  ];
});

const chartCards = computed(() => {
  const charts = data.value?.charts || {};

  return Object.entries(charts).map(([key, series]) => {
    const title = humanize(key);
    const valueKey = key === 'system_health' ? 'health_score' : 'value';

    return {
      title,
      hint: 'Selected period',
      ...lineChartProps(series, valueKey, title),
    };
  });
});

const healthMessage = computed(() => {
  if (criticalCount.value) {
    const noun = criticalCount.value === 1 ? 'scorecard is' : 'scorecards are';
    return `${criticalCount.value} ${noun} critical. Business score is ${businessScore.value}.`;
  }
  if (watchCount.value) {
    const noun = watchCount.value === 1 ? 'scorecard is' : 'scorecards are';
    return `${watchCount.value} ${noun} on watch. Business score is ${businessScore.value}.`;
  }
  if (businessScore.value < 60) {
    return `Business score is ${businessScore.value}. Portfolio health needs attention.`;
  }
  return data.value?.focus?.summary || 'Executive portfolio is healthy for the selected period.';
});

const healthTone = computed(() => {
  if (criticalCount.value || businessScore.value < 40) {
    return 'bg-rose-50 text-rose-800 ring-1 ring-rose-100';
  }
  if (watchCount.value || businessScore.value < 60) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (criticalCount.value || businessScore.value < 40) {
    return ExclamationTriangleIcon;
  }
  if (watchCount.value || businessScore.value < 60) {
    return ChartBarIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.dashboard) return;
    toast.error(message);
    store.error = null;
  },
);

function humanize(key) {
  return String(key || '')
    .replaceAll('_', ' ')
    .replace(/^\w/, (letter) => letter.toUpperCase());
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
    excellent: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
    good: 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
    watch: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
    critical: 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
  };
  return map[status] || 'bg-zinc-50 text-slate-600 ring-1 ring-inset ring-zinc-200';
}

function onApply(next) {
  filters.from = next.from;
  filters.to = next.to;
  load();
}

function load() {
  return store.fetchDashboard(type.value, { from: filters.from, to: filters.to }).catch(() => {});
}

async function onCapture() {
  try {
    await store.capture();
    if (store.successMessage) {
      toast.success(store.successMessage);
      store.successMessage = null;
    }
    await load();
  } catch {
    if (store.error) {
      toast.error(store.error);
      store.error = null;
    }
  }
}

watch(type, load);
onMounted(load);
</script>
