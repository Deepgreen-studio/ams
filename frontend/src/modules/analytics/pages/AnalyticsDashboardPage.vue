<template>
  <div>
    <!-- <PageHeader
      title="Analytics Overview"
      description="Enterprise analytics foundation across business, operational, application, customer, API, and system domains."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'analytics.dashboards' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Manage dashboards
        </RouterLink>
        <RouterLink
          :to="{ name: 'analytics.saved-views' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Saved views
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'analytics.dashboards' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Manage dashboards
        </RouterLink>
        <RouterLink
          :to="{ name: 'analytics.saved-views' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
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

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !store.overview" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.overview">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="category in store.overview.categories || []"
          :key="category.value"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ category.label }}</h3>
              <p class="mt-1 text-xs text-slate-500">{{ category.description }}</p>
            </div>
            <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
              {{ category.share }}%
            </span>
          </div>
          <p class="mt-4 text-2xl font-semibold text-slate-900">{{ formatNumber(category.event_count) }}</p>
          <p class="text-xs text-slate-500">events in period</p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Events over time"
          hint="All categories"
          :labels="trendLabels"
          :series="[{ key: 'count', label: 'Events', values: trendValues }]"
        />
        <SimpleBarChart title="Events by category" :data="store.overview.charts?.by_category || {}" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Recent dashboards</h3>
            <RouterLink :to="{ name: 'analytics.dashboards' }" class="text-sm font-medium text-brand-700 hover:underline">
              View all
            </RouterLink>
          </div>
          <ul class="divide-y divide-slate-100">
            <li
              v-for="item in store.overview.recent_dashboards?.items || []"
              :key="item.uuid"
              class="flex items-center justify-between gap-3 py-3"
            >
              <div>
                <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
                <p class="text-xs text-slate-500">{{ item.category }} · {{ item.status }}</p>
              </div>
              <RouterLink
                :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
                class="text-sm font-medium text-brand-700 hover:underline"
              >
                Open
              </RouterLink>
            </li>
            <li v-if="!(store.overview.recent_dashboards?.items || []).length" class="py-6 text-center text-sm text-slate-500">
              No published dashboards yet.
            </li>
          </ul>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Saved views</h3>
            <RouterLink :to="{ name: 'analytics.saved-views' }" class="text-sm font-medium text-brand-700 hover:underline">
              Manage
            </RouterLink>
          </div>
          <ul class="divide-y divide-slate-100">
            <li
              v-for="item in store.overview.saved_views?.items || []"
              :key="item.uuid"
              class="flex items-center justify-between gap-3 py-3"
            >
              <div>
                <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
                <p class="text-xs text-slate-500">{{ item.category }}</p>
              </div>
              <RouterLink
                :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
                class="text-sm font-medium text-brand-700 hover:underline"
              >
                Apply
              </RouterLink>
            </li>
            <li v-if="!(store.overview.saved_views?.items || []).length" class="py-6 text-center text-sm text-slate-500">
              No saved views yet.
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();

const kpiCards = computed(() => [
  { label: 'Total events', value: formatNumber(store.overview?.kpis?.total_events) },
  { label: 'Active categories', value: store.overview?.kpis?.categories_active ?? 0 },
  { label: 'Dashboards', value: store.overview?.kpis?.dashboards ?? 0 },
  { label: 'Saved views', value: store.overview?.kpis?.saved_views ?? 0 },
  { label: 'Report definitions', value: store.overview?.kpis?.report_definitions ?? 0 },
]);

const trendLabels = computed(() => (store.overview?.charts?.events_daily || []).map((row) => row.date));
const trendValues = computed(() => (store.overview?.charts?.events_daily || []).map((row) => row.count));

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchOverview();
}

async function onSaveView(next) {
  store.filters = { ...store.filters, ...next };
  const name = `View ${next.from || ''} → ${next.to || ''}`.trim();
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
}

onMounted(() => {
  store.fetchOverview();
});
</script>
