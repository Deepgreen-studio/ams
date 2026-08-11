<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-for="link in navLinks"
          :key="link.name"
          :to="{ name: link.name, params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] px-5 py-2.5 text-sm font-medium transition"
          :class="
            isActive(link.name)
              ? 'bg-brand-600 text-white hover:bg-brand-700'
              : 'border border-zinc-200 text-slate-700 hover:bg-zinc-50'
          "
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.loading && !analyticsStore.summary"
      class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <div v-for="n in 8" :key="n" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="mb-4 grid gap-4 lg:grid-cols-2">
      <SimpleLineChart
        title="Daily / Monthly users"
        hint="Engagement trends"
        :labels="analyticsStore.trend?.labels || []"
        :series="userSeries"
      />
      <SimpleLineChart
        title="Installs vs uninstalls"
        hint="Acquisition"
        :labels="analyticsStore.trend?.labels || []"
        :series="installSeries"
      />
      <SimpleLineChart
        title="Avg session time (sec)"
        hint="Engagement depth"
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
        hint="D1 / D7 / D30"
        :labels="analyticsStore.trend?.labels || []"
        :series="retentionSeries"
      />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">Top countries</h3>
          <p class="text-xs text-slate-500">{{ analyticsStore.topCountries.length || 0 }} shown</p>
        </div>
        <EmptyState
          v-if="!analyticsStore.topCountries.length"
          title="No country data"
          description="Ingest country analytics to populate this list."
          class="px-6 py-10"
        />
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="item in analyticsStore.topCountries"
            :key="item.country_code"
            class="flex items-center justify-between px-6 py-4 text-sm transition hover:bg-zinc-50/60"
          >
            <span class="font-semibold text-slate-900">{{
              item.country_name || item.country_code
            }}</span>
            <span class="text-slate-600">{{ item.users }} users</span>
          </li>
        </ul>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">Top devices</h3>
          <p class="text-xs text-slate-500">{{ analyticsStore.topDevices.length || 0 }} shown</p>
        </div>
        <EmptyState
          v-if="!analyticsStore.topDevices.length"
          title="No device data"
          description="Ingest device analytics to populate this list."
          class="px-6 py-10"
        />
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="(item, index) in analyticsStore.topDevices"
            :key="`${item.device_model}-${index}`"
            class="flex items-center justify-between px-6 py-4 text-sm transition hover:bg-zinc-50/60"
          >
            <span class="font-semibold text-slate-900">{{ item.device_model || 'Unknown' }}</span>
            <span class="text-slate-600">{{ item.users }} users</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowTrendingDownIcon,
  ArrowTrendingUpIcon,
  ClockIcon,
  DevicePhoneMobileIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const analyticsStore = useAnalyticsStore();
const toast = useToast();

const navLinks = [
  { name: 'applications.analytics.trends', label: 'Trends' },
  { name: 'applications.analytics.heatmap', label: 'Heatmap' },
  { name: 'applications.analytics.countries', label: 'Countries' },
  { name: 'applications.analytics.devices', label: 'Devices' },
];

const summaryCards = computed(() => {
  const s = analyticsStore.summary || {};
  return [
    {
      label: 'Active users',
      value: s.active_users ?? 0,
      icon: UsersIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Daily users',
      value: s.daily_users ?? 0,
      icon: UserGroupIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-600',
    },
    {
      label: 'Monthly users',
      value: s.monthly_users ?? 0,
      icon: UserGroupIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
    {
      label: 'Avg session (s)',
      value: s.avg_session_seconds ?? 0,
      icon: ClockIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-600',
    },
    {
      label: 'Installs',
      value: s.installs ?? 0,
      icon: ArrowTrendingUpIcon,
      iconBg: 'bg-teal-50',
      iconColor: 'text-teal-600',
    },
    {
      label: 'Uninstalls',
      value: s.uninstalls ?? 0,
      icon: ArrowTrendingDownIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-600',
    },
    {
      label: 'D1 retention',
      value: `${s.retention_d1 ?? 0}%`,
      icon: DevicePhoneMobileIcon,
      iconBg: 'bg-violet-50',
      iconColor: 'text-violet-600',
    },
    {
      label: 'D7 retention',
      value: `${s.retention_d7 ?? 0}%`,
      icon: DevicePhoneMobileIcon,
      iconBg: 'bg-orange-50',
      iconColor: 'text-orange-600',
    },
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

function isActive(name) {
  return route.name === name;
}

watch(
  () => analyticsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load analytics dashboard');
  },
);

onMounted(() => analyticsStore.fetchDashboard(route.params.id));
</script>
