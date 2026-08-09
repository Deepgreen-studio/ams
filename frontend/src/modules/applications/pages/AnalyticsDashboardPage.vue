<template>
  <div>
    <!-- <PageHeader
      :title="title"
      description="Active users, retention, installs, and engagement trends."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.analytics.trends', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Trends</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.analytics.heatmap', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Heatmap</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.analytics.countries', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Countries</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.analytics.devices', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >Devices</RouterLink
        >
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.analytics.trends', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Trends</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.analytics.heatmap', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Heatmap</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.analytics.countries', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Countries</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.analytics.devices', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >Devices</RouterLink
        >
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ analyticsStore.error }}
    </div>

    <div v-if="analyticsStore.summary" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 grid gap-4 lg:grid-cols-2">
      <SimpleLineChart
        title="Daily / Monthly users"
        :labels="analyticsStore.trend?.labels || []"
        :series="userSeries"
      />
      <SimpleLineChart
        title="Installs vs uninstalls"
        :labels="analyticsStore.trend?.labels || []"
        :series="installSeries"
      />
      <SimpleLineChart
        title="Avg session time (sec)"
        :labels="analyticsStore.trend?.labels || []"
        :series="[
          {
            key: 'session',
            label: 'Session',
            values: analyticsStore.trend?.avg_session_seconds || [],
          },
        ]"
      />
      <SimpleLineChart
        title="Retention"
        :labels="analyticsStore.trend?.labels || []"
        :series="retentionSeries"
      />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold">Top countries</div>
        <EmptyState
          v-if="!analyticsStore.topCountries.length"
          title="No country data"
          description="Ingest country analytics to populate this list."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in analyticsStore.topCountries"
            :key="item.country_code"
            class="flex items-center justify-between px-4 py-3 text-sm"
          >
            <span class="font-medium text-slate-900">{{
              item.country_name || item.country_code
            }}</span>
            <span class="text-slate-600">{{ item.users }} users</span>
          </li>
        </ul>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold">Top devices</div>
        <EmptyState
          v-if="!analyticsStore.topDevices.length"
          title="No device data"
          description="Ingest device analytics to populate this list."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="(item, index) in analyticsStore.topDevices"
            :key="`${item.device_model}-${index}`"
            class="flex items-center justify-between px-4 py-3 text-sm"
          >
            <span class="font-medium text-slate-900">{{ item.device_model || 'Unknown' }}</span>
            <span class="text-slate-600">{{ item.users }} users</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';

const route = useRoute();
const analyticsStore = useAnalyticsStore();

const title = computed(() => {
  const name = analyticsStore.application?.name;
  return name ? `${name} analytics` : 'Analytics Dashboard';
});

const summaryCards = computed(() => {
  const s = analyticsStore.summary || {};
  return [
    { label: 'Active users', value: s.active_users ?? 0 },
    { label: 'Daily users', value: s.daily_users ?? 0 },
    { label: 'Monthly users', value: s.monthly_users ?? 0 },
    { label: 'Avg session (s)', value: s.avg_session_seconds ?? 0 },
    { label: 'Installs', value: s.installs ?? 0 },
    { label: 'Uninstalls', value: s.uninstalls ?? 0 },
    { label: 'D1 retention', value: `${s.retention_d1 ?? 0}%` },
    { label: 'D7 retention', value: `${s.retention_d7 ?? 0}%` },
  ];
});

const userSeries = computed(() => [
  { key: 'dau', label: 'Daily', values: analyticsStore.trend?.daily_users || [] },
  { key: 'mau', label: 'Monthly', values: analyticsStore.trend?.monthly_users || [] },
  { key: 'active', label: 'Active', values: analyticsStore.trend?.active_users || [] },
]);

const installSeries = computed(() => [
  { key: 'installs', label: 'Installs', values: analyticsStore.trend?.installs || [] },
  { key: 'uninstalls', label: 'Uninstalls', values: analyticsStore.trend?.uninstalls || [] },
]);

const retentionSeries = computed(() => [
  { key: 'd1', label: 'D1', values: analyticsStore.trend?.retention_d1 || [] },
  { key: 'd7', label: 'D7', values: analyticsStore.trend?.retention_d7 || [] },
  { key: 'd30', label: 'D30', values: analyticsStore.trend?.retention_d30 || [] },
]);

onMounted(() => analyticsStore.fetchDashboard(route.params.id));
</script>
