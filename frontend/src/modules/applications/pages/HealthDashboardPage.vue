<template>
  <div>
    <PageHeader
      title="Health Dashboard"
      description="Health score, rates, memory, battery, and response time trends."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="monitoringStore.saving"
          @click="monitoringStore.refreshHealth(route.params.id)"
        >
          Refresh score
        </button>
        <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >Crash dashboard</RouterLink
        >
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ monitoringStore.error }}
    </div>
    <div
      v-if="monitoringStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ monitoringStore.successMessage }}
    </div>

    <div v-if="latest" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Health score</p>
        <p class="mt-1 text-3xl font-semibold" :class="scoreClass">{{ latest.health_score }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Crash rate</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ latest.crash_rate }}%</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Avg response</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ latest.avg_response_time_ms }} ms
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Memory / Battery</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">
          {{ latest.avg_memory_usage_mb }} MB · {{ latest.avg_battery_usage }}%
        </p>
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
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const latest = computed(() => monitoringStore.healthLatest);

const scoreClass = computed(() => {
  const score = latest.value?.health_score ?? 0;
  if (score >= 80) return 'text-emerald-700';
  if (score >= 50) return 'text-amber-700';
  return 'text-rose-700';
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

onMounted(() => monitoringStore.fetchHealthDashboard(route.params.id));
</script>
