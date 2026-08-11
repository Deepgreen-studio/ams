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

    <div v-if="monitoringStore.loading && !monitoringStore.crashSummary" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else-if="monitoringStore.crashSummary" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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

    <SimpleLineChart
      class="mb-4"
      title="Crash trends"
      hint="Last 7 days"
      :labels="monitoringStore.crashChart?.labels || []"
      :series="crashSeries"
    />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
        <h3 class="text-base font-semibold text-slate-900">Recent reports</h3>
        <p class="text-xs text-slate-500">
          {{ monitoringStore.recentCrashes.length || 0 }} shown
        </p>
      </div>

      <div v-if="monitoringStore.loading" class="space-y-3 px-6 py-5">
        <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
      </div>

      <EmptyState
        v-else-if="!monitoringStore.recentCrashes.length"
        title="No crash reports"
        description="Ingest crashes from the app SDK or create one via API."
        class="px-6 py-10"
      />

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Title</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Type</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Version
              </th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Device
              </th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in monitoringStore.recentCrashes"
              :key="item.uuid"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                <p class="text-xs text-slate-500">{{ item.occurrence_count }} occurrence(s)</p>
              </td>
              <td class="px-5 py-4">
                <CrashTypeBadge :type="item.type" :label="item.type_label" />
              </td>
              <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
                {{ item.version_label || '—' }}
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
                {{ item.device_model || '—' }}
              </td>
              <td class="px-5 py-4 text-right">
                <RouterLink
                  :to="{
                    name: 'applications.monitoring.crash',
                    params: { id: route.params.id, crashId: item.uuid },
                  }"
                  class="inline-flex items-center gap-1.5 rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50"
                >
                  <EyeIcon class="h-4 w-4" />
                  Details
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  BoltIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FireIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import CrashTypeBadge from '@/modules/applications/components/CrashTypeBadge.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const toast = useToast();

const navLinks = [
  { name: 'applications.monitoring.health', label: 'Health' },
  { name: 'applications.monitoring.devices', label: 'Devices' },
  { name: 'applications.monitoring.alerts', label: 'Alerts' },
  { name: 'applications.monitoring.charts', label: 'Charts' },
];

const summaryCards = computed(() => {
  const s = monitoringStore.crashSummary || {};
  return [
    {
      label: 'Total',
      value: s.total ?? 0,
      icon: Squares2X2Icon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Crashes',
      value: s.crash ?? 0,
      icon: FireIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-600',
    },
    {
      label: 'ANR',
      value: s.anr ?? 0,
      icon: ClockIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
    {
      label: 'API errors',
      value: s.api_error ?? 0,
      icon: BoltIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-600',
    },
    {
      label: 'Open',
      value: s.open ?? 0,
      icon: ExclamationTriangleIcon,
      iconBg: 'bg-orange-50',
      iconColor: 'text-orange-600',
    },
  ];
});

const crashSeries = computed(() => {
  const series = monitoringStore.crashChart?.series || {};
  return [
    { key: 'crash', label: 'Crash', values: series.crash || [] },
    { key: 'anr', label: 'ANR', values: series.anr || [] },
    { key: 'api_error', label: 'API Error', values: series.api_error || [] },
  ];
});

function isActive(name) {
  return route.name === name;
}

watch(
  () => monitoringStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load crash dashboard');
  },
);

onMounted(() => monitoringStore.fetchCrashDashboard(route.params.id));
</script>
