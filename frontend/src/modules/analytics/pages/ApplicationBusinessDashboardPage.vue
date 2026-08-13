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
      <p class="text-sm font-medium text-slate-900">Unable to load application analytics</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading usage and support metrics again.</p>
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
          title="Sessions"
          hint="Application sessions"
          v-bind="lineChartProps(data.charts?.sessions, 'value', 'Sessions')"
        />
        <SimpleLineChart
          title="Active users"
          hint="Unique users in period"
          v-bind="lineChartProps(data.charts?.active_users, 'value', 'Users')"
        />
        <SimpleLineChart
          title="Feature usage"
          hint="Feature events"
          v-bind="lineChartProps(data.charts?.feature_usage, 'value', 'Usage')"
        />
        <SimpleLineChart
          title="Support tickets"
          hint="New tickets"
          v-bind="lineChartProps(data.charts?.support_tickets, 'value', 'Tickets')"
        />
      </div>

      <section class="mt-4 overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Feature breakdown</h2>
          <p class="mt-0.5 text-xs text-slate-500">Subscriptions using each billed feature.</p>
        </div>

        <div v-if="!(data.feature_breakdown || []).length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No feature usage data</p>
          <p class="mt-1 text-xs text-slate-500">Feature adoption will appear here as subscriptions enable capabilities.</p>
        </div>

        <div v-else class="scrollbar-light overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Feature</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subscriptions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in data.feature_breakdown || []"
                :key="row.feature"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4 font-medium text-slate-900">{{ row.feature || '—' }}</td>
                <td class="px-5 py-4 text-slate-600">{{ formatNumber(row.count) }}</td>
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
  CursorArrowRaysIcon,
  InboxIcon,
  Squares2X2Icon,
  TicketIcon,
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
const data = computed(() => store.applications);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const sessions = computed(() => Number(data.value?.kpis?.application_sessions || 0));
const activeUsers = computed(() => Number(data.value?.kpis?.application_active_users || 0));
const featureUsage = computed(() => Number(data.value?.kpis?.feature_usage_count || 0));
const openTickets = computed(() => Number(data.value?.kpis?.support_tickets_open || 0));
const newTickets = computed(() => Number(data.value?.kpis?.support_tickets_new || 0));

const cards = computed(() => [
  {
    label: 'Sessions',
    value: formatNumber(sessions.value),
    hint: 'In this period',
    icon: CursorArrowRaysIcon,
    iconBg: sessions.value ? 'bg-violet-50' : 'bg-zinc-100',
    iconColor: sessions.value ? 'text-violet-500' : 'text-slate-500',
  },
  {
    label: 'Active users',
    value: formatNumber(activeUsers.value),
    hint: 'Unique users',
    icon: UserGroupIcon,
    iconBg: activeUsers.value ? 'bg-sky-50' : 'bg-zinc-100',
    iconColor: activeUsers.value ? 'text-sky-500' : 'text-slate-500',
  },
  {
    label: 'Feature usage',
    value: formatNumber(featureUsage.value),
    hint: 'Feature events',
    icon: Squares2X2Icon,
    iconBg: featureUsage.value ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: featureUsage.value ? 'text-emerald-500' : 'text-slate-500',
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
    label: 'New tickets',
    value: formatNumber(newTickets.value),
    hint: 'Opened in period',
    icon: InboxIcon,
    iconBg: newTickets.value ? 'bg-amber-50' : 'bg-zinc-100',
    iconColor: newTickets.value ? 'text-amber-500' : 'text-slate-500',
  },
]);

const healthMessage = computed(() => {
  if (openTickets.value) {
    return `${formatNumber(openTickets.value)} open support ticket${openTickets.value === 1 ? '' : 's'} may affect application experience.`;
  }
  if (!sessions.value) {
    return 'No application sessions in this period. Adjust the date range or wait for new usage.';
  }
  return 'Application usage looks healthy across the selected period.';
});

const healthTone = computed(() => {
  if (openTickets.value) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  if (!sessions.value) {
    return 'bg-sky-50 text-sky-800 ring-1 ring-sky-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (openTickets.value) {
    return TicketIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.applications) return;
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

function onApply(next) {
  Object.assign(filters, next);
  load();
}

async function load() {
  try {
    await store.fetchApplications({ from: filters.from, to: filters.to });
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
