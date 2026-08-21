<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.executive.trends' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ChartBarIcon class="h-4 w-4" />
        Trends
      </RouterLink>
      <RouterLink
        :to="{ name: 'analytics.executive.scorecards' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Scorecards
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      :model-value="filters"
      :show-category="false"
      @apply="onApply"
      @reset="onReset"
    >
      <input
        v-model.number="horizonDays"
        type="number"
        min="1"
        max="90"
        title="Forecast horizon in days"
        class="h-10 w-28 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
      />
    </EnterpriseFilterBar>

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 lg:grid-cols-2">
      <div v-for="n in 4" :key="n" class="h-56 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load executive forecast</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading revenue and customer projections again.</p>
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

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="MRR forecast"
          hint="Historical + projected"
          v-bind="forecastChart(data.forecast?.mrr || data.forecast?.forecast?.mrr, 'MRR')"
        />
        <SimpleLineChart
          title="Customer forecast"
          hint="Historical + projected"
          v-bind="forecastChart(data.forecast?.customers_total || data.forecast?.forecast?.customers_total, 'Customers')"
        />
        <SimpleLineChart
          title="Customer growth"
          hint="Observed growth"
          v-bind="lineChartProps(data.growth?.charts?.customer_growth, 'value', 'Customers')"
        />
        <SimpleLineChart
          title="Revenue trend"
          hint="Observed revenue"
          v-bind="lineChartProps(data.growth?.charts?.revenue_trend, 'value', 'Revenue')"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ChartBarIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';

const store = useExecutiveAnalyticsStore();
const toast = useToast();
const data = computed(() => store.forecast);
const horizonDays = ref(14);

const filters = reactive({
  from: new Date(Date.now() - 44 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const mrrBlock = computed(() => data.value?.forecast?.mrr || data.value?.forecast?.forecast?.mrr);
const projectedCount = computed(() => (mrrBlock.value?.projected || []).length);
const historicalCount = computed(() => {
  const historical = mrrBlock.value?.historical;
  if (Array.isArray(historical) && historical.length) {
    return historical.length;
  }
  return (mrrBlock.value?.combined || []).filter((row) => !row.projected).length;
});

const healthMessage = computed(() => {
  if (!projectedCount.value && !historicalCount.value) {
    return 'No forecast points yet. Capture snapshots and widen the date range to project growth.';
  }
  if (!projectedCount.value) {
    return 'Historical series loaded, but there is not enough snapshot history to project a horizon.';
  }
  return `Projecting ${horizonDays.value} day${horizonDays.value === 1 ? '' : 's'} ahead from ${historicalCount.value} historical point${historicalCount.value === 1 ? '' : 's'}.`;
});

const healthTone = computed(() => {
  if (!projectedCount.value) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => (projectedCount.value ? CheckCircleIcon : ExclamationTriangleIcon));

watch(
  () => store.error,
  (message) => {
    if (!message || !store.forecast) return;
    toast.error(message);
    store.error = null;
  },
);

function forecastChart(block, label) {
  return lineChartProps(block?.combined || block || [], 'value', label);
}

function clampHorizon(value) {
  const next = Number(value || 14);
  if (Number.isNaN(next)) return 14;
  return Math.min(90, Math.max(1, next));
}

function onApply(next) {
  filters.from = next.from;
  filters.to = next.to;
  horizonDays.value = clampHorizon(horizonDays.value);
  load();
}

function onReset(next) {
  filters.from = next.from;
  filters.to = next.to;
  horizonDays.value = 14;
  load();
}

function load() {
  return store
    .fetchForecast({
      from: filters.from,
      to: filters.to,
      horizon_days: clampHorizon(horizonDays.value),
    })
    .catch(() => {});
}

onMounted(load);
</script>
