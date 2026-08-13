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
      <p class="text-sm font-medium text-slate-900">Unable to load customer analytics</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading customer health metrics again.</p>
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
          title="Customer growth"
          hint="Total accounts"
          v-bind="lineChartProps(data.charts?.customer_growth, 'value', 'Customers')"
        />
        <SimpleLineChart
          title="New customers"
          hint="Created in period"
          v-bind="lineChartProps(data.charts?.new_customers, 'value', 'New')"
        />
        <SimpleLineChart
          title="Active customers"
          hint="Currently active"
          v-bind="lineChartProps(data.charts?.active_customers, 'value', 'Active')"
        />
        <SimpleLineChart
          title="Health score"
          hint="Average portfolio health"
          v-bind="lineChartProps(data.charts?.health_score, 'value', 'Health')"
        />
      </div>

      <section class="mt-4 overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">At-risk customers</h2>
          <p class="mt-0.5 text-xs text-slate-500">Accounts with elevated risk that need follow-up.</p>
        </div>

        <div v-if="!(data.at_risk || []).length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No at-risk customers</p>
          <p class="mt-1 text-xs text-slate-500">Customers flagged as medium, high, or critical risk will appear here.</p>
        </div>

        <div v-else class="scrollbar-light overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Customer</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Health</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Risk</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subscription</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in data.at_risk || []"
                :key="item.customer_uuid"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4">
                  <p class="font-medium text-slate-900">{{ item.display_name || '—' }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">{{ item.email || '—' }}</p>
                </td>
                <td class="px-5 py-4 text-slate-600">{{ item.health_score ?? '—' }}</td>
                <td class="px-5 py-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
                    :class="riskTone(item.risk_level)"
                  >
                    {{ item.risk_level || '—' }}
                  </span>
                </td>
                <td class="px-5 py-4 capitalize text-slate-600">{{ item.subscription_status || '—' }}</td>
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
  ChartBarIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  HeartIcon,
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
const data = computed(() => store.customers);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const total = computed(() => Number(data.value?.kpis?.customers_total || 0));
const active = computed(() => Number(data.value?.kpis?.customers_active || 0));
const created = computed(() => Number(data.value?.kpis?.customers_new || 0));
const avgHealth = computed(() => Number(data.value?.kpis?.avg_health_score || 0));
const atRisk = computed(() => Number(data.value?.kpis?.at_risk_customers || 0));

const cards = computed(() => [
  {
    label: 'Total',
    value: formatNumber(total.value),
    hint: 'All customers',
    icon: UserGroupIcon,
    iconBg: total.value ? 'bg-brand-50' : 'bg-zinc-100',
    iconColor: total.value ? 'text-brand-500' : 'text-slate-500',
  },
  {
    label: 'Active',
    value: formatNumber(active.value),
    hint: 'Currently active',
    icon: UserIcon,
    iconBg: active.value ? 'bg-sky-50' : 'bg-zinc-100',
    iconColor: active.value ? 'text-sky-500' : 'text-slate-500',
  },
  {
    label: 'New',
    value: formatNumber(created.value),
    hint: 'In this period',
    icon: UserPlusIcon,
    iconBg: created.value ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: created.value ? 'text-emerald-500' : 'text-slate-500',
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
    hint: 'Needs attention',
    icon: ExclamationTriangleIcon,
    iconBg: atRisk.value ? 'bg-orange-50' : 'bg-zinc-100',
    iconColor: atRisk.value ? 'text-orange-500' : 'text-slate-500',
  },
]);

const healthMessage = computed(() => {
  if (atRisk.value) {
    return `${formatNumber(atRisk.value)} customer${atRisk.value === 1 ? ' is' : 's are'} at risk. Review the table below and follow up.`;
  }
  if (!total.value) {
    return 'No customers in this period. Adjust the date range or wait for new accounts.';
  }
  return 'Customer health looks stable across the selected period.';
});

const healthTone = computed(() => {
  if (atRisk.value) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  if (!total.value) {
    return 'bg-sky-50 text-sky-800 ring-1 ring-sky-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (atRisk.value) {
    return ExclamationTriangleIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.customers) return;
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

function riskTone(level) {
  const map = {
    low: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    medium: 'bg-amber-50 text-amber-700 ring-amber-100',
    high: 'bg-orange-50 text-orange-700 ring-orange-100',
    critical: 'bg-rose-50 text-rose-700 ring-rose-100',
  };
  return map[level] || 'bg-zinc-50 text-slate-600 ring-zinc-200';
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

async function load() {
  try {
    await store.fetchCustomers({ from: filters.from, to: filters.to });
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
