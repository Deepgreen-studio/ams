<template>
  <div>
    <!-- <PageHeader
      title="Response History"
      description="Hourly API response volume and latency for the Integration Hub."
    /> -->
    <MonitoringSubnav />
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form class="mb-4 flex gap-3" @submit.prevent="load">
      <select v-model.number="hours" class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm">
        <option :value="24">24 hours</option>
        <option :value="48">48 hours</option>
        <option :value="72">72 hours</option>
        <option :value="168">7 days</option>
      </select>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Refresh
      </button>
    </form>

    <div class="mb-4 grid gap-4 lg:grid-cols-2">
      <SimpleLineChart
        title="Avg response (ms)"
        :points="store.history"
        value-key="avg_response_ms"
      />
      <SimpleLineChart
        title="Error rate (%)"
        :points="store.history"
        value-key="error_rate"
        stroke="#e11d48"
        fill="#e11d48"
      />
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Bucket</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Total</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Failed</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Avg ms</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Error %</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in store.history" :key="row.bucket">
            <td class="px-4 py-3 text-slate-700">{{ row.bucket }}</td>
            <td class="px-4 py-3">{{ row.total }}</td>
            <td class="px-4 py-3">{{ row.failed }}</td>
            <td class="px-4 py-3">{{ row.avg_response_ms }}</td>
            <td class="px-4 py-3">{{ row.error_rate }}</td>
          </tr>
          <tr v-if="!store.history.length">
            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No history yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import SimpleLineChart from '@/modules/monitoring/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const hours = ref(24);

onMounted(() => load());
function load() {
  store.fetchHistory({ hours: hours.value });
}
</script>
