<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.business' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ChartBarSquareIcon class="h-4 w-4" />
        Overview
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="filters"
      :show-category="false"
      @apply="onApply"
      @reset="onReset"
    />

    <div
      class="mb-4 flex flex-col gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:flex-row sm:items-end sm:justify-between"
    >
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Horizon (days)</label>
        <input v-model.number="filters.horizon_days" type="number" min="1" max="90" class="input w-28" />
      </div>
      <p class="text-xs text-slate-500 sm:pb-3">Projected days beyond the selected date range.</p>
      <button
        type="button"
        class="inline-flex h-12 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Update forecast
      </button>
    </div>

    <div v-if="store.loading && !hasData" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasData"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load growth & forecast</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading trend and projection charts again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <template v-else-if="hasData">
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

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Customer growth"
          hint="Total accounts"
          v-bind="lineChartProps(growth?.charts?.customer_growth, 'value', 'Customers')"
        />
        <SimpleLineChart
          title="Subscription growth"
          hint="Active subscriptions"
          v-bind="lineChartProps(growth?.charts?.subscription_growth, 'value', 'Subscriptions')"
        />
        <SimpleLineChart
          title="Revenue trend"
          hint="Monthly recurring revenue"
          v-bind="lineChartProps(growth?.charts?.revenue_trend, 'value', 'MRR')"
        />
        <SimpleLineChart
          title="Application usage"
          hint="Sessions in period"
          v-bind="lineChartProps(growth?.charts?.application_usage, 'value', 'Sessions')"
        />
      </div>

      <div class="mb-3">
        <h2 class="text-base font-semibold text-slate-900">Forecast charts</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Historical values plus a {{ filters.horizon_days }}-day linear projection.
        </p>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="MRR forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(forecast?.forecast?.mrr?.combined, 'value', 'MRR')"
        />
        <SimpleLineChart
          title="Customer forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(forecast?.forecast?.customers_total?.combined, 'value', 'Customers')"
        />
        <SimpleLineChart
          title="Subscription forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(forecast?.forecast?.subscriptions_active?.combined, 'value', 'Subscriptions')"
        />
        <SimpleLineChart
          title="Sessions forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(forecast?.forecast?.application_sessions?.combined, 'value', 'Sessions')"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowTrendingDownIcon,
  ArrowTrendingUpIcon,
  ChartBarSquareIcon,
  CheckCircleIcon,
  CreditCardIcon,
  CurrencyDollarIcon,
  CursorArrowRaysIcon,
  ExclamationTriangleIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';

const store = useBusinessAnalyticsStore();
const toast = useToast();
const growth = computed(() => store.growth);
const forecast = computed(() => store.forecast);
const hasData = computed(() => Boolean(store.growth || store.forecast));

const filters = reactive({
  from: new Date(Date.now() - 44 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
  horizon_days: 14,
});

const deltas = computed(() => growth.value?.deltas || {});
const mrrChange = computed(() => Number(deltas.value.mrr?.change || 0));
const customerChange = computed(() => Number(deltas.value.customers_total?.change || 0));

const kpiCards = computed(() => [
  deltaCard('Customer change', deltas.value.customers_total, UserGroupIcon, false),
  deltaCard('MRR change', deltas.value.mrr, CurrencyDollarIcon, true),
  deltaCard('Subscription change', deltas.value.subscriptions_active, CreditCardIcon, false),
  deltaCard('Session change', deltas.value.application_sessions, CursorArrowRaysIcon, false),
]);

const healthMessage = computed(() => {
  if (mrrChange.value < 0) {
    return 'MRR declined across this range. Review the forecast charts for projected recovery.';
  }
  if (customerChange.value < 0) {
    return 'Customer count declined across this range. Check churn and at-risk accounts.';
  }
  return 'Growth trends are stable. Forecasts use a linear projection from recent history.';
});

const healthTone = computed(() => {
  if (mrrChange.value < 0 || customerChange.value < 0) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (mrrChange.value < 0 || customerChange.value < 0) {
    return ExclamationTriangleIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !(store.growth || store.forecast)) return;
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

function deltaCard(label, delta, icon, money) {
  const change = Number(delta?.change || 0);
  const percent = delta?.change_percent;
  const positive = change > 0;
  const negative = change < 0;

  return {
    label,
    value: money ? formatMoney(change, true) : formatSigned(change),
    hint: percent == null ? 'Period delta' : `${percent > 0 ? '+' : ''}${percent}% vs start`,
    icon: negative ? ArrowTrendingDownIcon : positive ? ArrowTrendingUpIcon : icon,
    iconBg: negative ? 'bg-rose-50' : positive ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: negative ? 'text-rose-500' : positive ? 'text-emerald-500' : 'text-slate-500',
  };
}

function formatSigned(value) {
  const number = Number(value || 0);
  const formatted = new Intl.NumberFormat().format(Math.abs(number));
  if (number > 0) return `+${formatted}`;
  if (number < 0) return `-${formatted}`;
  return formatted;
}

function formatMoney(value, signed = false) {
  const number = Number(value || 0);
  const formatted = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(Math.abs(number));
  if (!signed) return formatted;
  if (number > 0) return `+${formatted}`;
  if (number < 0) return `-${formatted}`;
  return formatted;
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

function onReset(next) {
  Object.assign(filters, next, { horizon_days: 14 });
  load();
}

async function load() {
  try {
    await Promise.all([
      store.fetchGrowth({ from: filters.from, to: filters.to }),
      store.fetchForecast({
        from: filters.from,
        to: filters.to,
        horizon_days: filters.horizon_days,
      }),
    ]);
  } catch {
    // First-load retry UI / toast from watchers.
  }
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  load();
});
</script>
