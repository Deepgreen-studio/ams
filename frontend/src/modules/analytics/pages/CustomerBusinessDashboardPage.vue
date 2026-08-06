<template>
  <div>
    <PageHeader title="Customer Dashboard" description="Growth, active customers, health scores, and at-risk accounts." />
    <AnalyticsSubnav />
    <BusinessAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        From
        <input v-model="filters.from" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        To
        <input v-model="filters.to" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ store.error }}</div>

    <template v-if="data">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart title="Customer growth" :points="points(data.charts?.customer_growth)" value-key="value" stroke="#0f766e" fill="#0f766e" />
        <SimpleLineChart title="New customers" :points="points(data.charts?.new_customers)" value-key="value" />
        <SimpleLineChart title="Active customers" :points="points(data.charts?.active_customers)" value-key="value" stroke="#0369a1" fill="#0369a1" />
        <SimpleLineChart title="Health score" :points="points(data.charts?.health_score)" value-key="value" stroke="#b45309" fill="#b45309" />
      </div>

      <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">At-risk customers</div>
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3">Customer</th>
              <th class="px-4 py-3">Health</th>
              <th class="px-4 py-3">Risk</th>
              <th class="px-4 py-3">Subscription</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!(data.at_risk || []).length">
              <td colspan="4" class="px-4 py-8 text-center text-slate-500">No at-risk customers.</td>
            </tr>
            <tr v-for="item in data.at_risk || []" :key="item.customer_uuid" class="border-t border-slate-100">
              <td class="px-4 py-3">
                <p class="font-medium text-slate-900">{{ item.display_name }}</p>
                <p class="text-xs text-slate-500">{{ item.email }}</p>
              </td>
              <td class="px-4 py-3">{{ item.health_score }}</td>
              <td class="px-4 py-3 capitalize">{{ item.risk_level }}</td>
              <td class="px-4 py-3 capitalize">{{ item.subscription_status || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import BusinessAnalyticsSubnav from '@/modules/analytics/components/BusinessAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';

const store = useBusinessAnalyticsStore();
const data = computed(() => store.customers);
const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const cards = computed(() => [
  { label: 'Total', value: data.value?.kpis?.customers_total ?? 0 },
  { label: 'Active', value: data.value?.kpis?.customers_active ?? 0 },
  { label: 'New', value: data.value?.kpis?.customers_new ?? 0 },
  { label: 'Avg health', value: data.value?.kpis?.avg_health_score ?? 0 },
  { label: 'At risk', value: data.value?.kpis?.at_risk_customers ?? 0 },
]);

function points(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

async function load() {
  await store.fetchCustomers({ ...filters });
}

onMounted(load);
</script>
