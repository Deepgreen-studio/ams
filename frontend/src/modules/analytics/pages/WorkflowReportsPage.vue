<template>
  <div>
    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onApply"
      @export="(format) => store.exportReport(format, 'workflows')"
    />

    <div v-if="store.loading && !store.workflows" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.workflows"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load workflow reports</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading success rate, failures, and processing time again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="store.workflows">
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
          title="Workflow trends"
          hint="Created, completed, and failed"
          :labels="store.workflows.trends?.labels || []"
          :series="trendSeries"
        />
        <SimpleBarChart title="By status" :data="store.workflows.by_status || {}" />
      </div>

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Top workflows</h2>
          <p class="mt-0.5 text-xs text-slate-500">Highest-volume workflow instances in this period.</p>
        </div>
        <div v-if="!(store.workflows.top_workflows || []).length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No instances</p>
          <p class="mt-1 text-xs text-slate-500">Workflow runs will appear here once they start.</p>
        </div>
        <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
          <li
            v-for="row in store.workflows.top_workflows"
            :key="row.workflow_id"
            class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-slate-900">{{ row.workflow_name }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ row.success_rate }}% success</p>
            </div>
            <span class="text-sm font-medium text-slate-900">{{ formatNumber(row.total) }}</span>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import {
  ArrowPathIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClockIcon,
  RectangleStackIcon,
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
  const data = store.workflows || {};
  return [
    kpi('Instances', data.total, 'Workflow runs in period', RectangleStackIcon, 'brand'),
    kpi('Success', data.success, 'Completed instances', CheckCircleIcon, 'emerald'),
    kpi('Failures', data.failures, 'Errored instances', XCircleIcon, 'rose'),
    kpi('Success rate', `${data.success_rate ?? 0}%`, 'Completed vs total', ChartBarIcon, 'emerald'),
    kpi('Avg processing (s)', data.avg_processing_seconds, 'Mean runtime', ClockIcon, 'amber'),
    kpi('In progress', data.in_progress, 'Currently running', ArrowPathIcon, 'sky'),
  ];
});

const trendSeries = computed(() => [
  { key: 'created', label: 'Created', values: store.workflows?.trends?.created || [] },
  { key: 'completed', label: 'Completed', values: store.workflows?.trends?.completed || [] },
  { key: 'failed', label: 'Failed', values: store.workflows?.trends?.failed || [] },
]);

watch(
  () => store.error,
  (message) => {
    if (!message || !store.workflows) return;
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
  store.fetchWorkflows().catch(() => {});
}

function reload() {
  store.fetchWorkflows().catch(() => {});
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  store.fetchWorkflows().catch(() => {});
});
</script>
