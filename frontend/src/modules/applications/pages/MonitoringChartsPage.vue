<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'applications.monitoring.health', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          Health
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          Crash dashboard
        </RouterLink>
        <SelectBox
          v-model="metric"
          size="lg"
          wrapper-class="min-w-[12rem]"
          :options="metricOptions"
          @change="reload"
        />
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.loading && !monitoringStore.charts"
      class="grid gap-4 lg:grid-cols-2"
    >
      <div v-for="n in 2" :key="n" class="h-64 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <SimpleLineChart
        title="Crash / ANR / API volume"
        hint="Last 14 days"
        :labels="monitoringStore.charts?.crash_chart?.labels || []"
        :series="crashSeries"
      />
      <SimpleLineChart
        :title="metricChartTitle"
        hint="Selected metric"
        :labels="monitoringStore.charts?.health_chart?.labels || []"
        :series="[
          {
            key: 'metric',
            label: metricLabel,
            values: monitoringStore.charts?.health_chart?.values || [],
          },
        ]"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const toast = useToast();
const metric = ref('health_score');

const metricOptions = [
  { value: 'health_score', label: 'Health score' },
  { value: 'crash_rate', label: 'Crash rate' },
  { value: 'anr_rate', label: 'ANR rate' },
  { value: 'api_error_rate', label: 'API error rate' },
  { value: 'response_time', label: 'Response time' },
  { value: 'memory', label: 'Memory' },
  { value: 'battery', label: 'Battery' },
];

const metricLabel = computed(
  () => metricOptions.find((item) => item.value === metric.value)?.label || metric.value,
);

const metricChartTitle = computed(() => `Health metric: ${metricLabel.value}`);

const crashSeries = computed(() => {
  const series = monitoringStore.charts?.crash_chart?.series || {};
  return [
    { key: 'crash', label: 'Crash', values: series.crash || [] },
    { key: 'anr', label: 'ANR', values: series.anr || [] },
    { key: 'api_error', label: 'API Error', values: series.api_error || [] },
  ];
});

watch(
  () => monitoringStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load monitoring charts');
  },
);

onMounted(reload);

async function reload() {
  await monitoringStore.fetchCharts(route.params.id, { metric: metric.value });
}
</script>
