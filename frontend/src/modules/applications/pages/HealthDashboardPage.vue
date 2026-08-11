<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50 disabled:opacity-60"
          :disabled="monitoringStore.saving"
          @click="refresh"
        >
          <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': monitoringStore.saving }" />
          Refresh score
        </button>
        <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Crash dashboard
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.loading && !latest"
      class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else-if="latest" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight" :class="card.valueClass">
            {{ card.value }}
          </p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <SimpleLineChart
        title="Health score"
        :labels="monitoringStore.healthChart?.labels || []"
        :series="[
          {
            key: 'health',
            label: 'Score',
            values: monitoringStore.healthChart?.health_score || [],
          },
        ]"
      />
      <SimpleLineChart
        title="Error rates"
        :labels="monitoringStore.healthChart?.labels || []"
        :series="rateSeries"
      />
      <SimpleLineChart
        title="Response time"
        :labels="monitoringStore.healthChart?.labels || []"
        :series="[
          {
            key: 'rt',
            label: 'ms',
            values: monitoringStore.healthChart?.avg_response_time_ms || [],
          },
        ]"
      />
      <SimpleLineChart
        title="Memory & battery"
        :labels="monitoringStore.healthChart?.labels || []"
        :series="resourceSeries"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowPathIcon,
  BoltIcon,
  ClockIcon,
  CpuChipIcon,
  HeartIcon,
} from '@heroicons/vue/24/outline';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const toast = useToast();
const latest = computed(() => monitoringStore.healthLatest);

const scoreClass = computed(() => {
  const score = latest.value?.health_score ?? 0;
  if (score >= 80) return 'text-emerald-700';
  if (score >= 50) return 'text-amber-700';
  return 'text-rose-700';
});

const summaryCards = computed(() => {
  const item = latest.value || {};
  return [
    {
      label: 'Health score',
      value: item.health_score ?? 0,
      valueClass: scoreClass.value,
      icon: HeartIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-600',
    },
    {
      label: 'Crash rate',
      value: `${item.crash_rate ?? 0}%`,
      valueClass: 'text-slate-900',
      icon: BoltIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-600',
    },
    {
      label: 'Avg response',
      value: `${item.avg_response_time_ms ?? 0} ms`,
      valueClass: 'text-slate-900',
      icon: ClockIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
    {
      label: 'Memory / Battery',
      value: `${item.avg_memory_usage_mb ?? 0} MB · ${item.avg_battery_usage ?? 0}%`,
      valueClass: 'text-slate-900 text-lg',
      icon: CpuChipIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-600',
    },
  ];
});

const rateSeries = computed(() => [
  { key: 'crash', label: 'Crash', values: monitoringStore.healthChart?.crash_rate || [] },
  { key: 'anr', label: 'ANR', values: monitoringStore.healthChart?.anr_rate || [] },
  { key: 'api', label: 'API', values: monitoringStore.healthChart?.api_error_rate || [] },
]);

const resourceSeries = computed(() => [
  {
    key: 'mem',
    label: 'Memory MB',
    values: monitoringStore.healthChart?.avg_memory_usage_mb || [],
  },
  {
    key: 'batt',
    label: 'Battery usage',
    values: monitoringStore.healthChart?.avg_battery_usage || [],
  },
]);

watch(
  () => monitoringStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load health dashboard');
  },
);

watch(
  () => monitoringStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

async function refresh() {
  await monitoringStore.refreshHealth(route.params.id);
}

onMounted(() => monitoringStore.fetchHealthDashboard(route.params.id));
</script>
