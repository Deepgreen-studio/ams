<template>
  <div>
    <!-- <PageHeader
      :title="title"
      description="Crash reports, ANR events, and API errors with trend charts."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.monitoring.health', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Health</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.monitoring.devices', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Devices</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.monitoring.alerts', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Alerts</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.monitoring.charts', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >Charts</RouterLink
        >
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.monitoring.health', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Health</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.monitoring.devices', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Devices</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.monitoring.alerts', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Alerts</RouterLink
        >
        <RouterLink
          :to="{ name: 'applications.monitoring.charts', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >Charts</RouterLink
        >
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ monitoringStore.error }}
    </div>

    <div v-if="monitoringStore.crashSummary" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <SimpleLineChart
      class="mb-4"
      title="Crash trends"
      hint="Last 7 days"
      :labels="monitoringStore.crashChart?.labels || []"
      :series="crashSeries"
    />

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">
        Recent reports
      </div>
      <div v-if="monitoringStore.loading" class="space-y-3 p-4">
        <div v-for="n in 4" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!monitoringStore.recentCrashes.length"
        title="No crash reports"
        description="Ingest crashes from the app SDK or create one via API."
      />
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Title</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Version
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Device
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in monitoringStore.recentCrashes" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.occurrence_count }} occurrence(s)</p>
            </td>
            <td class="px-4 py-3">{{ item.type_label || item.type }}</td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.version_label || '—' }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.device_model || '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{
                  name: 'applications.monitoring.crash',
                  params: { id: route.params.id, crashId: item.uuid },
                }"
                class="text-xs font-medium text-brand-700 hover:underline"
                >Details</RouterLink
              >
            </td>
          </tr>
        </tbody>
      </table>
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
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';

const route = useRoute();
const monitoringStore = useMonitoringStore();

const title = computed(() => {
  const name = monitoringStore.application?.name;
  return name ? `${name} crash dashboard` : 'Crash Dashboard';
});

const summaryCards = computed(() => {
  const s = monitoringStore.crashSummary || {};
  return [
    { label: 'Total', value: s.total ?? 0 },
    { label: 'Crashes', value: s.crash ?? 0 },
    { label: 'ANR', value: s.anr ?? 0 },
    { label: 'API errors', value: s.api_error ?? 0 },
    { label: 'Open', value: s.open ?? 0 },
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

onMounted(() => monitoringStore.fetchCrashDashboard(route.params.id));
</script>
