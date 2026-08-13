<template>
  <div>
    <Teleport defer to="#page-header-actions">
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
      v-model="filters"
      :show-category="false"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load business overview</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading portfolio metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <template v-else-if="data">
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
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

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Customer growth"
          hint="Accounts over time"
          v-bind="lineChartProps(data.charts?.customer_growth, 'value', 'Customers')"
        />
        <SimpleLineChart
          title="Revenue (MRR)"
          hint="Monthly recurring revenue"
          v-bind="lineChartProps(data.charts?.revenue_trend, 'value', 'MRR')"
        />
        <SimpleLineChart
          title="Subscription growth"
          hint="Active subscriptions"
          v-bind="lineChartProps(data.charts?.subscription_growth, 'value', 'Subscriptions')"
        />
        <SimpleLineChart
          title="Application usage"
          hint="Sessions in period"
          v-bind="lineChartProps(data.charts?.application_usage, 'value', 'Sessions')"
        />
        <SimpleLineChart
          title="Support tickets"
          hint="New tickets"
          v-bind="lineChartProps(data.charts?.support_tickets, 'value', 'Tickets')"
        />
        <SimpleLineChart
          title="Avg health score"
          hint="Portfolio health"
          v-bind="lineChartProps(data.charts?.health_score, 'value', 'Health')"
        />
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="MRR forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(data.forecast?.mrr?.combined, 'value', 'MRR')"
        />
        <SimpleLineChart
          title="Customer forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(data.forecast?.customers_total?.combined, 'value', 'Customers')"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import {
  CameraIcon,
  CheckCircleIcon,
  CreditCardIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  HeartIcon,
  TicketIcon,
  UserGroupIcon,
  UserIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';

const store = useBusinessAnalyticsStore();
const toast = useToast();
const data = computed(() => store.overview);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const atRisk = computed(() => Number(data.value?.kpis?.at_risk_customers || 0));
const openTickets = computed(() => Number(data.value?.kpis?.support_tickets_open || 0));
const customers = computed(() => Number(data.value?.kpis?.customers_total || 0));
const activeCustomers = computed(() => Number(data.value?.kpis?.customers_active || 0));
const newCustomers = computed(() => Number(data.value?.kpis?.customers_new || 0));
const subscriptions = computed(() => Number(data.value?.kpis?.subscriptions_active || 0));
const avgHealth = computed(() => Number(data.value?.kpis?.avg_health_score || 0));

const kpiCards = computed(() => [
  {
    label: 'Customers',
    value: formatNumber(customers.value),
    hint: 'Total accounts',
    icon: UserGroupIcon,
    iconBg: customers.value ? 'bg-brand-50' : 'bg-zinc-100',
    iconColor: customers.value ? 'text-brand-500' : 'text-slate-500',
  },
  {
    label: 'Active customers',
    value: formatNumber(activeCustomers.value),
    hint: 'Currently active',
    icon: UserIcon,
    iconBg: activeCustomers.value ? 'bg-sky-50' : 'bg-zinc-100',
    iconColor: activeCustomers.value ? 'text-sky-500' : 'text-slate-500',
  },
  {
    label: 'New customers',
    value: formatNumber(newCustomers.value),
    hint: 'In this period',
    icon: UserPlusIcon,
    iconBg: newCustomers.value ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: newCustomers.value ? 'text-emerald-500' : 'text-slate-500',
  },
  {
    label: 'MRR',
    value: formatMoney(data.value?.kpis?.mrr),
    hint: 'Monthly recurring revenue',
    icon: CurrencyDollarIcon,
    iconBg: Number(data.value?.kpis?.mrr || 0) ? 'bg-amber-50' : 'bg-zinc-100',
    iconColor: Number(data.value?.kpis?.mrr || 0) ? 'text-amber-500' : 'text-slate-500',
  },
  {
    label: 'Active subscriptions',
    value: formatNumber(subscriptions.value),
    hint: 'Live plans',
    icon: CreditCardIcon,
    iconBg: subscriptions.value ? 'bg-violet-50' : 'bg-zinc-100',
    iconColor: subscriptions.value ? 'text-violet-500' : 'text-slate-500',
  },
  {
    label: 'Open tickets',
    value: formatNumber(openTickets.value),
    hint: 'Support queue',
    icon: TicketIcon,
    iconBg: openTickets.value ? 'bg-rose-50' : 'bg-zinc-100',
    iconColor: openTickets.value ? 'text-rose-500' : 'text-slate-500',
  },
  {
    label: 'Avg health',
    value: avgHealth.value,
    hint: 'Portfolio score',
    icon: HeartIcon,
    iconBg: avgHealth.value ? 'bg-teal-50' : 'bg-zinc-100',
    iconColor: avgHealth.value ? 'text-teal-500' : 'text-slate-500',
  },
  {
    label: 'At risk',
    value: formatNumber(atRisk.value),
    hint: 'Customers needing attention',
    icon: ExclamationTriangleIcon,
    iconBg: atRisk.value ? 'bg-orange-50' : 'bg-zinc-100',
    iconColor: atRisk.value ? 'text-orange-500' : 'text-slate-500',
  },
]);

const healthMessage = computed(() => {
  if (atRisk.value) {
    return `${formatNumber(atRisk.value)} customer${atRisk.value === 1 ? ' is' : 's are'} at risk. Review health scores and follow up.`;
  }
  if (openTickets.value) {
    return `${formatNumber(openTickets.value)} open support ticket${openTickets.value === 1 ? '' : 's'} may affect customer health.`;
  }
  return 'Portfolio health looks stable across the selected period.';
});

const healthTone = computed(() => {
  if (atRisk.value) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  if (openTickets.value) {
    return 'bg-sky-50 text-sky-800 ring-1 ring-sky-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (atRisk.value) {
    return ExclamationTriangleIcon;
  }
  if (openTickets.value) {
    return TicketIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.overview) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(
    Number(value || 0),
  );
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

async function load() {
  try {
    await store.fetchOverview({ from: filters.from, to: filters.to });
  } catch {
    // First-load retry UI / toast from watchers.
  }
}

async function onCapture() {
  try {
    await store.capture();
    await load();
  } catch {
    // Toast from store.error.
  }
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  load();
});
</script>
