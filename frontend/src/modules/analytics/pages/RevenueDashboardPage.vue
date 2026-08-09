<template>
  <div>
    <!-- <PageHeader title="Revenue Dashboard" description="MRR, subscription growth, plan mix, and revenue forecast." /> -->
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
        <SimpleLineChart title="MRR trend" :points="points(data.charts?.mrr)" value-key="value" stroke="#b45309" fill="#b45309" />
        <SimpleLineChart title="Period revenue" :points="points(data.charts?.revenue_period)" value-key="value" />
        <SimpleLineChart title="Active subscriptions" :points="points(data.charts?.subscription_growth)" value-key="value" stroke="#0f766e" fill="#0f766e" />
        <SimpleLineChart title="New subscriptions" :points="points(data.charts?.new_subscriptions)" value-key="value" stroke="#0369a1" fill="#0369a1" />
        <SimpleLineChart title="MRR forecast" :points="points(data.forecast?.mrr?.combined)" value-key="value" stroke="#b45309" fill="#b45309" />
        <SimpleLineChart title="Subscription forecast" :points="points(data.forecast?.subscriptions_active?.combined)" value-key="value" stroke="#0f766e" fill="#0f766e" />
      </div>

      <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Revenue by plan</div>
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3">Plan</th>
              <th class="px-4 py-3">Subscriptions</th>
              <th class="px-4 py-3">Revenue</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in data.by_plan || []" :key="row.plan_type" class="border-t border-slate-100">
              <td class="px-4 py-3 capitalize">{{ row.plan_type }}</td>
              <td class="px-4 py-3">{{ row.count }}</td>
              <td class="px-4 py-3">{{ formatMoney(row.revenue) }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import BusinessAnalyticsSubnav from '@/modules/analytics/components/BusinessAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useBusinessAnalyticsStore } from '@/modules/analytics/stores/businessAnalytics';

const store = useBusinessAnalyticsStore();
const data = computed(() => store.revenue);
const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const cards = computed(() => [
  { label: 'MRR', value: formatMoney(data.value?.kpis?.mrr) },
  { label: 'Period revenue', value: formatMoney(data.value?.kpis?.revenue_period) },
  { label: 'ARPU', value: formatMoney(data.value?.kpis?.arpu) },
  { label: 'Active subs', value: data.value?.kpis?.subscriptions_active ?? 0 },
  { label: 'New subs', value: data.value?.kpis?.subscriptions_new ?? 0 },
]);

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function points(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

async function load() {
  await store.fetchRevenue({ ...filters });
}

onMounted(load);
</script>
