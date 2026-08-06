<template>
  <div>
    <PageHeader title="Monitoring charts" description="Compare crash volume and health metric trends.">
      <template #actions>
        <select v-model="metric" class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm" @change="reload">
          <option value="health_score">Health score</option>
          <option value="crash_rate">Crash rate</option>
          <option value="anr_rate">ANR rate</option>
          <option value="api_error_rate">API error rate</option>
          <option value="response_time">Response time</option>
          <option value="memory">Memory</option>
          <option value="battery">Battery</option>
        </select>
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="monitoringStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ monitoringStore.error }}</div>

    <div class="grid gap-4 lg:grid-cols-2">
      <SimpleLineChart
        title="Crash / ANR / API volume"
        :labels="monitoringStore.charts?.crash_chart?.labels || []"
        :series="crashSeries"
      />
      <SimpleLineChart
        :title="`Health metric: ${metric}`"
        :labels="monitoringStore.charts?.health_chart?.labels || []"
        :series="[{ key: 'metric', label: metric, values: monitoringStore.charts?.health_chart?.values || [] }]"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const metric = ref('health_score');

const crashSeries = computed(() => {
  const series = monitoringStore.charts?.crash_chart?.series || {};
  return [
    { key: 'crash', label: 'Crash', values: series.crash || [] },
    { key: 'anr', label: 'ANR', values: series.anr || [] },
    { key: 'api_error', label: 'API Error', values: series.api_error || [] },
  ];
});

onMounted(reload);

async function reload() {
  await monitoringStore.fetchCharts(route.params.id, { metric: metric.value });
}
</script>
