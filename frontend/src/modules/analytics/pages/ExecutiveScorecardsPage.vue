<template>
  <div>
    <!-- <PageHeader title="Business Scorecards" description="Composite performance scorecards across revenue, growth, support, compliance, and operations." /> -->
    <AnalyticsSubnav />
    <ExecutiveAnalyticsSubnav />

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

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in data.scorecards || []" :key="card.key" class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="flex items-start justify-between">
            <p class="text-sm font-medium text-slate-800">{{ card.label }}</p>
            <span class="text-xs capitalize text-slate-500">{{ card.status }}</span>
          </div>
          <p class="mt-3 text-3xl font-semibold text-slate-900">{{ card.score }}</p>
          <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-brand-600" :style="{ width: `${Math.min(100, card.score)}%` }" />
          </div>
          <p class="mt-2 text-xs text-slate-500">{{ card.unit_label }}: {{ card.value }}</p>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-sm font-semibold text-slate-900">Performance indicators</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div v-for="item in data.performance || []" :key="item.key" class="rounded-lg bg-slate-50 px-3 py-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">{{ item.label }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ formatValue(item) }}</p>
            <p v-if="item.delta?.change != null" class="mt-1 text-xs text-slate-500">
              Δ {{ item.delta.change }} ({{ item.delta.change_percent ?? '—' }}%)
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import ExecutiveAnalyticsSubnav from '@/modules/analytics/components/ExecutiveAnalyticsSubnav.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';

const store = useExecutiveAnalyticsStore();
const data = computed(() => store.scorecards);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

function formatValue(item) {
  if (item.unit === 'currency') {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(item.value || 0));
  }
  return item.value;
}

async function load() {
  await store.fetchScorecards({ ...filters });
}

onMounted(load);
</script>
