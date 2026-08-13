<template>
  <div>
    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onApply"
      @export="(format) => store.exportReport(format, 'automation')"
    />

    <div v-if="store.loading && !store.automation" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.automation"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load automation reports</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading executions, success rate, and processing time again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="store.automation">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Execution trends"
          hint="Total, success, and failed"
          :labels="store.automation.trends?.labels || []"
          :series="trendSeries"
        />
        <SimpleBarChart title="By trigger type" :data="store.automation.by_trigger || {}" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By status" :data="store.automation.by_status || {}" />
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Top rules</h2>
            <p class="mt-0.5 text-xs text-slate-500">Highest-volume automation rules in this period.</p>
          </div>
          <div v-if="!(store.automation.top_rules || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No executions</p>
            <p class="mt-1 text-xs text-slate-500">Automation rules will appear here once they run.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="row in store.automation.top_rules"
              :key="row.rule_id"
              class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ row.rule_name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ row.success_rate }}% success</p>
              </div>
              <span class="text-sm font-medium text-slate-900">{{ formatNumber(row.total) }}</span>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import {
  BoltIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClockIcon,
  ForwardIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();
const toast = useToast();

const cards = computed(() => {
  const data = store.automation || {};
  return [
    kpi('Executions', data.total, 'Rule runs in period', BoltIcon, 'violet'),
    kpi('Success', data.success, 'Completed runs', CheckCircleIcon, 'emerald'),
    kpi('Failed', data.failed, 'Errored runs', XCircleIcon, 'rose'),
    kpi('Success rate', `${data.success_rate ?? 0}%`, 'Completed vs total', ChartBarIcon, 'brand'),
    kpi('Avg processing (s)', data.avg_processing_seconds, 'Mean runtime', ClockIcon, 'amber'),
    kpi('Skipped', data.skipped, 'Bypassed runs', ForwardIcon, 'sky'),
  ];
});

const trendSeries = computed(() => [
  { key: 'executions', label: 'Total', values: store.automation?.trends?.executions || [] },
  { key: 'success', label: 'Success', values: store.automation?.trends?.success || [] },
  { key: 'failed', label: 'Failed', values: store.automation?.trends?.failed || [] },
]);

watch(
  () => store.error,
  (message) => {
    if (!message || !store.automation) return;
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

function kpi(label, value, hint, icon, tone) {
  const numeric = typeof value === 'number' ? value : Number(String(value).replace(/[^\d.-]/g, '')) || 0;
  const tones = {
    brand: ['bg-brand-50', 'text-brand-500'],
    rose: ['bg-rose-50', 'text-rose-500'],
    sky: ['bg-sky-50', 'text-sky-500'],
    emerald: ['bg-emerald-50', 'text-emerald-500'],
    amber: ['bg-amber-50', 'text-amber-500'],
    violet: ['bg-violet-50', 'text-violet-500'],
  };
  const [iconBg, iconColor] = numeric ? tones[tone] : ['bg-zinc-100', 'text-slate-500'];

  return {
    label,
    value: typeof value === 'number' ? formatNumber(value) : value ?? 0,
    hint,
    icon,
    iconBg,
    iconColor,
  };
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchAutomation().catch(() => {});
}

function reload() {
  store.fetchAutomation().catch(() => {});
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  store.fetchAutomation().catch(() => {});
});
</script>
