<template>
  <div>
    <PageHeader title="Application Dashboard" description="Sessions, active users, feature usage, and support volume." />
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
        <SimpleLineChart title="Sessions" :points="points(data.charts?.sessions)" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
        <SimpleLineChart title="Active users" :points="points(data.charts?.active_users)" value-key="value" stroke="#0369a1" fill="#0369a1" />
        <SimpleLineChart title="Feature usage" :points="points(data.charts?.feature_usage)" value-key="value" stroke="#0f766e" fill="#0f766e" />
        <SimpleLineChart title="Support tickets" :points="points(data.charts?.support_tickets)" value-key="value" stroke="#be123c" fill="#be123c" />
      </div>

      <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Feature breakdown</div>
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3">Feature</th>
              <th class="px-4 py-3">Subscriptions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!(data.feature_breakdown || []).length">
              <td colspan="2" class="px-4 py-8 text-center text-slate-500">No feature usage data.</td>
            </tr>
            <tr v-for="row in data.feature_breakdown || []" :key="row.feature" class="border-t border-slate-100">
              <td class="px-4 py-3">{{ row.feature }}</td>
              <td class="px-4 py-3">{{ row.count }}</td>
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
const data = computed(() => store.applications);
const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const cards = computed(() => [
  { label: 'Sessions', value: data.value?.kpis?.application_sessions ?? 0 },
  { label: 'Active users', value: data.value?.kpis?.application_active_users ?? 0 },
  { label: 'Feature usage', value: data.value?.kpis?.feature_usage_count ?? 0 },
  { label: 'Open tickets', value: data.value?.kpis?.support_tickets_open ?? 0 },
  { label: 'New tickets', value: data.value?.kpis?.support_tickets_new ?? 0 },
]);

function points(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

async function load() {
  await store.fetchApplications({ ...filters });
}

onMounted(load);
</script>
