<template>
  <div>
    <MonitoringSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form
      class="mb-4 flex flex-col gap-3 rounded-[12px] bg-white px-5 py-5 ring-1 ring-zinc-100 sm:flex-row sm:items-center sm:px-6"
      @submit.prevent="load"
    >
      <select
        v-model.number="hours"
        class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
      >
        <option :value="24">24 hours</option>
        <option :value="48">48 hours</option>
        <option :value="72">72 hours</option>
        <option :value="168">7 days</option>
      </select>
      <button
        type="submit"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
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

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full divide-y divide-zinc-100 text-sm">
        <thead class="bg-zinc-50/80">
          <tr>
            <th
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Bucket
            </th>
            <th
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Total
            </th>
            <th
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Failed
            </th>
            <th
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Avg ms
            </th>
            <th
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Error %
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-for="row in store.history" :key="row.bucket" class="hover:bg-zinc-50/80">
            <td class="px-5 py-3.5 text-slate-700">{{ row.bucket }}</td>
            <td class="px-5 py-3.5 font-medium text-slate-900">{{ row.total }}</td>
            <td class="px-5 py-3.5 text-slate-700">{{ row.failed }}</td>
            <td class="px-5 py-3.5 text-slate-700">{{ row.avg_response_ms }}</td>
            <td class="px-5 py-3.5 text-slate-700">{{ row.error_rate }}</td>
          </tr>
          <tr v-if="!store.history.length">
            <td colspan="5" class="px-5 py-10 text-center text-slate-500">No history yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
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
