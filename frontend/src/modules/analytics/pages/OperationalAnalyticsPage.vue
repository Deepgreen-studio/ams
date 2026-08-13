<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.delivery' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <PaperAirplaneIcon class="h-4 w-4" />
        Delivery reports
      </RouterLink>
      <RouterLink
        :to="{ name: 'analytics.automation' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <BoltIcon class="h-4 w-4" />
        Automation reports
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onApply"
      @export="(format) => store.exportReport(format, 'overview')"
    />

    <div v-if="store.loading && !store.dashboard" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.dashboard"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load operational analytics</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading delivery, automation, workflow, and AI metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="store.dashboard">
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
          title="Notification delivery"
          hint="Sent vs failed"
          :labels="store.dashboard.charts?.notifications_daily?.labels || []"
          :series="notificationSeries"
        />
        <SimpleLineChart
          title="Automation executions"
          hint="Success vs failed"
          :labels="store.dashboard.charts?.automation_daily?.labels || []"
          :series="automationSeries"
        />
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Workflow outcomes"
          hint="Completed vs failed"
          :labels="store.dashboard.charts?.workflow_daily?.labels || []"
          :series="workflowSeries"
        />
        <SimpleLineChart
          title="AI usage"
          hint="Daily requests"
          :labels="store.dashboard.charts?.ai_daily?.labels || []"
          :series="aiSeries"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <SimpleBarChart title="Notifications by channel" :data="store.dashboard.notifications?.by_channel || {}" />
        <SimpleBarChart title="Automation by status" :data="store.dashboard.automation?.by_status || {}" />
        <SimpleBarChart title="Workflows by status" :data="store.dashboard.workflows?.by_status || {}" />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BoltIcon,
  ClockIcon,
  CursorArrowRaysIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  PaperAirplaneIcon,
  RectangleStackIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();
const toast = useToast();

const kpis = computed(() => store.dashboard?.kpis || {});

const kpiCards = computed(() => [
  kpi('Notifications sent', kpis.value.notifications_sent, 'Delivered in period', PaperAirplaneIcon, 'brand'),
  kpi('Notifications failed', kpis.value.notifications_failed, 'Delivery failures', ExclamationTriangleIcon, 'rose'),
  kpi('Read rate', `${kpis.value.read_rate ?? 0}%`, 'Opened notifications', EyeIcon, 'sky'),
  kpi('Click rate', `${kpis.value.click_rate ?? 0}%`, 'Engaged notifications', CursorArrowRaysIcon, 'emerald'),
  kpi('Avg delivery (s)', kpis.value.avg_delivery_seconds, 'Mean time to deliver', ClockIcon, 'amber'),
  kpi('Automation executions', kpis.value.automation_executions, 'Rule runs', BoltIcon, 'violet'),
  kpi('Workflow success', `${kpis.value.workflow_success_rate ?? 0}%`, 'Completed instances', RectangleStackIcon, 'emerald'),
  kpi('AI requests', kpis.value.ai_requests, `${formatNumber(kpis.value.ai_tokens ?? 0)} tokens`, SparklesIcon, 'sky'),
]);

const notificationSeries = computed(() => [
  { key: 'sent', label: 'Sent', values: store.dashboard?.charts?.notifications_daily?.sent || [] },
  { key: 'failed', label: 'Failed', values: store.dashboard?.charts?.notifications_daily?.failed || [] },
]);

const automationSeries = computed(() => [
  { key: 'success', label: 'Success', values: store.dashboard?.charts?.automation_daily?.success || [] },
  { key: 'failed', label: 'Failed', values: store.dashboard?.charts?.automation_daily?.failed || [] },
]);

const workflowSeries = computed(() => [
  { key: 'completed', label: 'Completed', values: store.dashboard?.charts?.workflow_daily?.completed || [] },
  { key: 'failed', label: 'Failed', values: store.dashboard?.charts?.workflow_daily?.failed || [] },
]);

const aiSeries = computed(() => [
  { key: 'requests', label: 'Requests', values: store.dashboard?.charts?.ai_daily?.requests || [] },
]);

watch(
  () => store.error,
  (message) => {
    if (!message || !store.dashboard) return;
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
  store.fetchDashboard().catch(() => {});
}

function reload() {
  store.fetchDashboard().catch(() => {});
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  store.fetchDashboard().catch(() => {});
});
</script>
