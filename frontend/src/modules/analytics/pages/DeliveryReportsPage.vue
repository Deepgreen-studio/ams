<template>
  <div>
    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onApply"
      @export="(format) => store.exportReport(format, 'notifications')"
    />

    <div v-if="store.loading && !store.notifications" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.notifications"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load delivery reports</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading sent, failed, and engagement metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="store.notifications">
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
          title="Delivery volume"
          hint="Sent vs failed"
          :labels="store.notifications.trends?.labels || []"
          :series="volumeSeries"
        />
        <SimpleLineChart
          title="Engagement"
          hint="Reads and clicks"
          :labels="store.notifications.trends?.labels || []"
          :series="engagementSeries"
        />
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By channel" :data="store.notifications.by_channel || {}" />
        <SimpleBarChart title="By status" :data="store.notifications.by_status || {}" />
      </div>

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Top events</h2>
          <p class="mt-0.5 text-xs text-slate-500">Highest-volume notification event keys.</p>
        </div>
        <div v-if="!(store.notifications.top_events || []).length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No events</p>
          <p class="mt-1 text-xs text-slate-500">Notification event keys will appear here once traffic is recorded.</p>
        </div>
        <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
          <li
            v-for="row in store.notifications.top_events"
            :key="row.event_key"
            class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
          >
            <span class="truncate text-sm text-slate-700">{{ row.event_key }}</span>
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
  CheckCircleIcon,
  ClockIcon,
  CursorArrowRaysIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  PaperAirplaneIcon,
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
  const data = store.notifications || {};
  return [
    kpi('Sent', data.sent, 'Delivered notifications', PaperAirplaneIcon, 'brand'),
    kpi('Failed', data.failed, 'Delivery failures', ExclamationTriangleIcon, 'rose'),
    kpi('Avg delivery (s)', data.avg_delivery_seconds, 'Mean time to deliver', ClockIcon, 'amber'),
    kpi('Read rate', `${data.read_rate ?? 0}%`, 'Opened notifications', EyeIcon, 'sky'),
    kpi('Click rate', `${data.click_rate ?? 0}%`, 'Engaged notifications', CursorArrowRaysIcon, 'emerald'),
    kpi('Delivery success', `${data.delivery_success_rate ?? 0}%`, 'Successful sends', CheckCircleIcon, 'emerald'),
  ];
});

const volumeSeries = computed(() => [
  { key: 'sent', label: 'Sent', values: store.notifications?.trends?.sent || [] },
  { key: 'failed', label: 'Failed', values: store.notifications?.trends?.failed || [] },
]);

const engagementSeries = computed(() => [
  { key: 'reads', label: 'Reads', values: store.notifications?.trends?.reads || [] },
  { key: 'clicks', label: 'Clicks', values: store.notifications?.trends?.clicks || [] },
]);

watch(
  () => store.error,
  (message) => {
    if (!message || !store.notifications) return;
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
  store.fetchNotifications().catch(() => {});
}

function reload() {
  store.fetchNotifications().catch(() => {});
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  store.fetchNotifications().catch(() => {});
});
</script>
