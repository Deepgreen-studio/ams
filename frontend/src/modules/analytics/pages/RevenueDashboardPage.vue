<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.business.growth' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ChartBarIcon class="h-4 w-4" />
        Growth & forecast
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="filters"
      :show-category="false"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load revenue analytics</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading MRR and subscription metrics again.</p>
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

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="card in cards"
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
          title="MRR trend"
          hint="Monthly recurring revenue"
          v-bind="lineChartProps(data.charts?.mrr, 'value', 'MRR')"
        />
        <SimpleLineChart
          title="Period revenue"
          hint="Recognized in range"
          v-bind="lineChartProps(data.charts?.revenue_period, 'value', 'Revenue')"
        />
        <SimpleLineChart
          title="Active subscriptions"
          hint="Live plans"
          v-bind="lineChartProps(data.charts?.subscription_growth, 'value', 'Subscriptions')"
        />
        <SimpleLineChart
          title="New subscriptions"
          hint="Created in period"
          v-bind="lineChartProps(data.charts?.new_subscriptions, 'value', 'New')"
        />
        <SimpleLineChart
          title="MRR forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(data.forecast?.mrr?.combined, 'value', 'MRR')"
        />
        <SimpleLineChart
          title="Subscription forecast"
          hint="Historical + projected"
          v-bind="lineChartProps(data.forecast?.subscriptions_active?.combined, 'value', 'Subscriptions')"
        />
      </div>

      <section class="mt-4 overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Revenue by plan</h2>
          <p class="mt-0.5 text-xs text-slate-500">Active and trial subscriptions grouped by plan type.</p>
        </div>

        <div v-if="!(data.by_plan || []).length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No plan revenue yet</p>
          <p class="mt-1 text-xs text-slate-500">Active subscriptions will appear here once billed plans exist.</p>
        </div>

        <div v-else class="scrollbar-light overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Plan</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subscriptions</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Revenue</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in data.by_plan || []"
                :key="row.plan_type"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4 capitalize font-medium text-slate-900">{{ row.plan_type || '—' }}</td>
                <td class="px-5 py-4 text-slate-600">{{ formatNumber(row.count) }}</td>
                <td class="px-5 py-4 text-slate-600">{{ formatMoney(row.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BanknotesIcon,
  ChartBarIcon,
  CheckCircleIcon,
  CreditCardIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  PlusCircleIcon,
  ReceiptPercentIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';

const store = useBusinessAnalyticsStore();
const toast = useToast();
const data = computed(() => store.revenue);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const mrr = computed(() => Number(data.value?.kpis?.mrr || 0));
const periodRevenue = computed(() => Number(data.value?.kpis?.revenue_period || 0));
const arpu = computed(() => Number(data.value?.kpis?.arpu || 0));
const activeSubs = computed(() => Number(data.value?.kpis?.subscriptions_active || 0));
const newSubs = computed(() => Number(data.value?.kpis?.subscriptions_new || 0));

const cards = computed(() => [
  {
    label: 'MRR',
    value: formatMoney(mrr.value),
    hint: 'Monthly recurring revenue',
    icon: CurrencyDollarIcon,
    iconBg: mrr.value ? 'bg-amber-50' : 'bg-zinc-100',
    iconColor: mrr.value ? 'text-amber-500' : 'text-slate-500',
  },
  {
    label: 'Period revenue',
    value: formatMoney(periodRevenue.value),
    hint: 'Recognized in range',
    icon: BanknotesIcon,
    iconBg: periodRevenue.value ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: periodRevenue.value ? 'text-emerald-500' : 'text-slate-500',
  },
  {
    label: 'ARPU',
    value: formatMoney(arpu.value),
    hint: 'Average per active sub',
    icon: ReceiptPercentIcon,
    iconBg: arpu.value ? 'bg-sky-50' : 'bg-zinc-100',
    iconColor: arpu.value ? 'text-sky-500' : 'text-slate-500',
  },
  {
    label: 'Active subs',
    value: formatNumber(activeSubs.value),
    hint: 'Live plans',
    icon: CreditCardIcon,
    iconBg: activeSubs.value ? 'bg-violet-50' : 'bg-zinc-100',
    iconColor: activeSubs.value ? 'text-violet-500' : 'text-slate-500',
  },
  {
    label: 'New subs',
    value: formatNumber(newSubs.value),
    hint: 'In this period',
    icon: PlusCircleIcon,
    iconBg: newSubs.value ? 'bg-brand-50' : 'bg-zinc-100',
    iconColor: newSubs.value ? 'text-brand-500' : 'text-slate-500',
  },
]);

const healthMessage = computed(() => {
  if (!activeSubs.value) {
    return 'No active subscriptions in this period. New plans will appear once they are billed.';
  }
  if (!newSubs.value) {
    return 'Revenue is flowing, but there were no new subscriptions in this range.';
  }
  return 'Revenue collection looks healthy across the selected period.';
});

const healthTone = computed(() => {
  if (!activeSubs.value) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  if (!newSubs.value) {
    return 'bg-sky-50 text-sky-800 ring-1 ring-sky-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (!activeSubs.value) {
    return ExclamationTriangleIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.revenue) return;
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
    await store.fetchRevenue({ from: filters.from, to: filters.to });
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
