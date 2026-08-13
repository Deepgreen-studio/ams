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
      <RouterLink
        :to="{ name: 'analytics.executive.forecast' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <ArrowTrendingUpIcon class="h-4 w-4" />
        Forecast
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <div
      class="mb-4 flex flex-col gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:px-8 lg:flex-row lg:items-end lg:justify-between"
    >
      <form class="flex flex-wrap items-end gap-3" @submit.prevent="load">
        <div class="min-w-[12rem]">
          <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Granularity</label>
          <SelectBox v-model="granularity" size="lg" :options="granularityOptions" />
        </div>
        <button
          type="submit"
          class="inline-flex h-12 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Apply filters
        </button>
        <button
          type="button"
          class="inline-flex h-12 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset
        </button>
      </form>
    </div>

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 lg:grid-cols-2">
      <div v-for="n in 4" :key="n" class="h-56 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load executive trends</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading snapshot history again.</p>
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

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart title="MRR trend" hint="From captured snapshots" v-bind="chartProps('mrr', 'MRR')" />
        <SimpleLineChart title="Customers" hint="Total customers" v-bind="chartProps('customers_total', 'Customers')" />
        <SimpleLineChart title="Business score" hint="Composite score" v-bind="chartProps('business_score', 'Score')" />
        <SimpleLineChart title="System health" hint="Reliability score" v-bind="chartProps('system_health_score', 'Health')" />
      </div>

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
          <h2 class="text-base font-semibold text-slate-900">Trend snapshots</h2>
          <p class="mt-0.5 text-xs text-slate-500">{{ granularityLabel }} leadership history from captured snapshots.</p>
        </div>
        <div v-if="store.loading && !series.length" class="space-y-3 px-6 py-5 sm:px-8">
          <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <div v-else-if="!series.length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No trend snapshots yet</p>
          <p class="mt-1 text-xs text-slate-500">Capture an executive snapshot to seed history.</p>
        </div>
        <div v-else class="scrollbar-light overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Period</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">MRR</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Customers</th>
                <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">Active</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Business score</th>
                <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">SLA breached</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in series"
                :key="row.label"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4 font-medium text-slate-900">{{ row.label }}</td>
                <td class="px-5 py-4 text-slate-700">{{ formatMoney(row.mrr) }}</td>
                <td class="px-5 py-4 text-slate-700">{{ row.customers_total }}</td>
                <td class="hidden px-5 py-4 text-slate-700 md:table-cell">{{ row.customers_active }}</td>
                <td class="px-5 py-4 text-slate-700">{{ row.business_score }}</td>
                <td class="hidden px-5 py-4 text-slate-700 lg:table-cell">{{ row.support_sla_breached }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowTrendingUpIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';

const store = useExecutiveAnalyticsStore();
const toast = useToast();
const data = computed(() => store.trends);
const granularity = ref('monthly');
const series = computed(() => data.value?.series || []);

const granularityOptions = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'yearly', label: 'Yearly' },
];

const granularityLabel = computed(() => {
  const match = granularityOptions.find((option) => option.value === granularity.value);
  return match?.label || 'Monthly';
});

const latestScore = computed(() => Number(series.value[series.value.length - 1]?.business_score ?? 0));

const healthMessage = computed(() => {
  if (!series.value.length) {
    return 'No captured snapshots in this range. Capture a snapshot from an executive dashboard to seed trends.';
  }
  if (latestScore.value < 40) {
    return `Latest business score is ${latestScore.value}. Trend history shows critical portfolio health.`;
  }
  if (latestScore.value < 60) {
    return `Latest business score is ${latestScore.value}. Watch the trajectory before it degrades further.`;
  }
  return `${granularityLabel.value} trends are populated from ${series.value.length} snapshot${series.value.length === 1 ? '' : 's'}.`;
});

const healthTone = computed(() => {
  if (!series.value.length) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  if (latestScore.value < 40) {
    return 'bg-rose-50 text-rose-800 ring-1 ring-rose-100';
  }
  if (latestScore.value < 60) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (!series.value.length || latestScore.value < 60) {
    return latestScore.value < 40 ? ExclamationTriangleIcon : ChartBarIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.trends) return;
    toast.error(message);
    store.error = null;
  },
);

function chartProps(field, label) {
  return lineChartProps(series.value, field, label);
}

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function load() {
  return store.fetchTrends({ granularity: granularity.value }).catch(() => {});
}

function onReset() {
  granularity.value = 'monthly';
  load();
}

onMounted(load);
</script>
