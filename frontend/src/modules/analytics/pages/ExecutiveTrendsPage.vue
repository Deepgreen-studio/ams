<template>
  <div>
    <!-- <PageHeader title="Executive Trends" description="Monthly, quarterly, and yearly leadership trends." /> -->
    <AnalyticsSubnav />
    <ExecutiveAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        Granularity
        <select v-model="granularity" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm">
          <option value="monthly">Monthly</option>
          <option value="quarterly">Quarterly</option>
          <option value="yearly">Yearly</option>
        </select>
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart title="MRR trend" :points="points('mrr')" value-key="value" stroke="#b45309" fill="#b45309" />
        <SimpleLineChart title="Customers" :points="points('customers_total')" value-key="value" stroke="#0f766e" fill="#0f766e" />
        <SimpleLineChart title="Business score" :points="points('business_score')" value-key="value" stroke="#0369a1" fill="#0369a1" />
        <SimpleLineChart title="System health" :points="points('system_health_score')" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
      </div>

      <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Period</th>
              <th class="px-4 py-3">MRR</th>
              <th class="px-4 py-3">Customers</th>
              <th class="px-4 py-3">Active</th>
              <th class="px-4 py-3">Business score</th>
              <th class="px-4 py-3">SLA breached</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in data.series || []" :key="row.label" class="border-t border-slate-100">
              <td class="px-4 py-3 font-medium text-slate-800">{{ row.label }}</td>
              <td class="px-4 py-3">{{ formatMoney(row.mrr) }}</td>
              <td class="px-4 py-3">{{ row.customers_total }}</td>
              <td class="px-4 py-3">{{ row.customers_active }}</td>
              <td class="px-4 py-3">{{ row.business_score }}</td>
              <td class="px-4 py-3">{{ row.support_sla_breached }}</td>
            </tr>
            <tr v-if="!(data.series || []).length">
              <td colspan="6" class="px-4 py-10 text-center text-slate-500">No trend snapshots yet. Capture an executive snapshot to seed history.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import ExecutiveAnalyticsSubnav from '@/modules/analytics/components/ExecutiveAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';

const store = useExecutiveAnalyticsStore();
const data = computed(() => store.trends);
const granularity = ref('monthly');

function points(field) {
  return (data.value?.series || []).map((row) => ({
    label: row.label,
    value: Number(row[field] || 0),
  }));
}

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value || 0));
}

async function load() {
  await store.fetchTrends({ granularity: granularity.value });
}

onMounted(load);
</script>
