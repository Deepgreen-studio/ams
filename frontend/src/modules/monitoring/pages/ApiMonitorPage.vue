<template>
  <div>
    <!-- <PageHeader
      title="API Monitor"
      description="Response time, availability, authentication, and rate-limit status."
    /> -->
    <MonitoringSubnav />
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase text-slate-500">Avg response</p>
          <p class="mt-2 text-2xl font-semibold">{{ data.avg_response_ms }} ms</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase text-slate-500">Error rate</p>
          <p class="mt-2 text-2xl font-semibold">{{ data.summary?.error_rate ?? 0 }}%</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase text-slate-500">Auth success</p>
          <p class="mt-2 text-2xl font-semibold">{{ data.authentication?.success_rate ?? 0 }}%</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase text-slate-500">Rate-limit hits</p>
          <p class="mt-2 text-2xl font-semibold">{{ data.rate_limits?.hits ?? 0 }}</p>
        </div>
      </div>
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="(value, key) in data.statuses || {}"
          :key="key"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs uppercase text-slate-500">{{ key.replaceAll('_', '') }}</p>
          <p class="mt-2 capitalize font-semibold text-slate-900">{{ value }}</p>
        </div>
      </div>
      <SimpleLineChart
        title="API response history"
        :points="data.history || []"
        value-key="avg_response_ms"
      />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import SimpleLineChart from '@/modules/monitoring/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const data = computed(() => store.apiMonitor);
onMounted(() => store.fetchApiMonitor());
</script>
