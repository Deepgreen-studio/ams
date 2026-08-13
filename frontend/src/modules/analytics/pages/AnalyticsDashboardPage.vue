<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.dashboards' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Manage dashboards
      </RouterLink>
      <RouterLink
        :to="{ name: 'analytics.saved-views' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <BookmarkIcon class="h-4 w-4" />
        Saved views
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="store.categories"
      show-save-view
      @apply="onApply"
      @reset="onApply"
      @save-view="onSaveView"
    />

    <div v-if="store.loading && !store.overview" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.overview"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load analytics overview</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading events and category metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="store.overview">
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

      <div class="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <button
          v-for="category in store.overview.categories || []"
          :key="category.value"
          type="button"
          class="rounded-[12px] bg-white p-5 text-left ring-1 transition hover:ring-brand-200"
          :class="store.filters.category === category.value ? 'ring-brand-300' : 'ring-zinc-100'"
          @click="selectCategory(category.value)"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-slate-900">{{ category.label }}</h3>
              <p class="mt-1 text-xs text-slate-500">{{ category.description }}</p>
            </div>
            <span
              class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
              :class="shareTone(category.event_count)"
            >
              {{ category.share }}%
            </span>
          </div>
          <p class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
            {{ formatNumber(category.event_count) }}
          </p>
          <p class="mt-0.5 text-xs text-slate-400">events in period</p>
        </button>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Events over time"
          hint="All categories"
          :labels="trendLabels"
          :series="[{ key: 'count', label: 'Events', values: trendValues }]"
        />
        <SimpleBarChart title="Events by category" :data="store.overview.charts?.by_category || {}" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent dashboards</h2>
              <p class="mt-0.5 text-xs text-slate-500">Published boards from this workspace.</p>
            </div>
            <RouterLink
              :to="{ name: 'analytics.dashboards' }"
              class="text-sm font-medium text-brand-700 hover:text-brand-600"
            >
              View all
            </RouterLink>
          </div>
          <div v-if="!(store.overview.recent_dashboards?.items || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No published dashboards yet</p>
            <p class="mt-1 text-xs text-slate-500">Create a dashboard to pin charts and KPIs here.</p>
            <RouterLink
              :to="{ name: 'analytics.dashboards' }"
              class="mt-6 inline-flex rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            >
              Manage dashboards
            </RouterLink>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="item in store.overview.recent_dashboards.items"
              :key="item.uuid"
            >
              <RouterLink
                :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
                class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3 transition hover:bg-zinc-50"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-slate-900">{{ item.name }}</p>
                  <p class="mt-0.5 text-xs capitalize text-slate-500">{{ item.category }} · {{ item.status }}</p>
                </div>
                <span class="text-sm font-medium text-brand-700">Open</span>
              </RouterLink>
            </li>
          </ul>
        </section>

        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Saved views</h2>
              <p class="mt-0.5 text-xs text-slate-500">Reusable date and category presets.</p>
            </div>
            <RouterLink
              :to="{ name: 'analytics.saved-views' }"
              class="text-sm font-medium text-brand-700 hover:text-brand-600"
            >
              Manage
            </RouterLink>
          </div>
          <div v-if="!(store.overview.saved_views?.items || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No saved views yet</p>
            <p class="mt-1 text-xs text-slate-500">Save the current filters to reopen this range later.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="item in store.overview.saved_views.items"
              :key="item.uuid"
            >
              <RouterLink
                :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
                class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3 transition hover:bg-zinc-50"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-slate-900">{{ item.name }}</p>
                  <p class="mt-0.5 text-xs capitalize text-slate-500">{{ item.category }}</p>
                </div>
                <span class="text-sm font-medium text-brand-700">Apply</span>
              </RouterLink>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BookmarkIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
  TagIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const toast = useToast();

const totalEvents = computed(() => Number(store.overview?.kpis?.total_events || 0));
const activeCategories = computed(() => Number(store.overview?.kpis?.categories_active || 0));
const dashboards = computed(() => Number(store.overview?.kpis?.dashboards || 0));
const savedViews = computed(() => Number(store.overview?.kpis?.saved_views || 0));
const reportDefinitions = computed(() => Number(store.overview?.kpis?.report_definitions || 0));

const kpiCards = computed(() => [
  {
    label: 'Total events',
    value: formatNumber(totalEvents.value),
    hint: 'Recorded in this period',
    icon: ChartBarIcon,
    iconBg: totalEvents.value ? 'bg-brand-50' : 'bg-zinc-100',
    iconColor: totalEvents.value ? 'text-brand-500' : 'text-slate-500',
  },
  {
    label: 'Active categories',
    value: activeCategories.value,
    hint: 'Domains with traffic',
    icon: TagIcon,
    iconBg: activeCategories.value ? 'bg-sky-50' : 'bg-zinc-100',
    iconColor: activeCategories.value ? 'text-sky-500' : 'text-slate-500',
  },
  {
    label: 'Dashboards',
    value: dashboards.value,
    hint: 'Published boards',
    icon: Squares2X2Icon,
    iconBg: dashboards.value ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: dashboards.value ? 'text-emerald-500' : 'text-slate-500',
  },
  {
    label: 'Saved views',
    value: savedViews.value,
    hint: 'Reusable filter presets',
    icon: BookmarkIcon,
    iconBg: savedViews.value ? 'bg-amber-50' : 'bg-zinc-100',
    iconColor: savedViews.value ? 'text-amber-500' : 'text-slate-500',
  },
  {
    label: 'Report definitions',
    value: reportDefinitions.value,
    hint: 'Saved report builders',
    icon: DocumentTextIcon,
    iconBg: reportDefinitions.value ? 'bg-violet-50' : 'bg-zinc-100',
    iconColor: reportDefinitions.value ? 'text-violet-500' : 'text-slate-500',
  },
]);

const healthMessage = computed(() => {
  if (!totalEvents.value) {
    return 'No analytics events in this period. Adjust the date range or wait for new activity.';
  }
  if (!reportDefinitions.value) {
    return 'Event volume looks healthy. Create a report definition to export this data on a schedule.';
  }
  return 'Analytics collection is healthy across the selected period.';
});

const healthTone = computed(() => {
  if (!totalEvents.value) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  if (!reportDefinitions.value) {
    return 'bg-sky-50 text-sky-800 ring-1 ring-sky-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (!totalEvents.value) {
    return ExclamationTriangleIcon;
  }
  if (!reportDefinitions.value) {
    return ClipboardDocumentListIcon;
  }
  return CheckCircleIcon;
});

const trendLabels = computed(() => (store.overview?.charts?.events_daily || []).map((row) => row.date));
const trendValues = computed(() => (store.overview?.charts?.events_daily || []).map((row) => row.count));

watch(
  () => store.error,
  (message) => {
    if (!message || !store.overview) return;
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

function shareTone(count) {
  if (!count) {
    return 'bg-zinc-50 text-slate-600 ring-zinc-200';
  }
  return 'bg-brand-50 text-brand-700 ring-brand-100';
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchOverview().catch(() => {});
}

function selectCategory(value) {
  const next = store.filters.category === value ? '' : value;
  store.filters = { ...store.filters, category: next };
  store.fetchOverview().catch(() => {});
}

async function onSaveView(next) {
  store.filters = { ...store.filters, ...next };
  const name = `View ${next.from || ''} → ${next.to || ''}`.trim();
  try {
    await store.createSavedView({
      name,
      category: next.category || 'business',
      status: 'published',
      filters: {
        from: next.from,
        to: next.to,
        category: next.category || null,
      },
    });
    await store.fetchOverview();
  } catch {
    // Toast is shown from store.error.
  }
}

function reload() {
  store.fetchOverview().catch(() => {});
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  store.fetchOverview().catch(() => {});
});
</script>
