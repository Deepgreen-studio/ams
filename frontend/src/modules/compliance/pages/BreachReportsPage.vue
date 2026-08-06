<template>
  <div>
    <PageHeader
      title="Breach reports"
      description="Summary statistics and risk matrix snapshot for compliance reporting."
    />
    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-3 text-sm font-semibold text-slate-900">Status distribution</h2>
      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="(count, status) in byStatus"
          :key="status"
          class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm"
        >
          <span class="capitalize text-slate-600">{{ status.replaceAll('_', ' ') }}</span>
          <span class="font-semibold text-slate-900">{{ count }}</span>
        </div>
      </div>
      <p class="mt-4 text-xs text-slate-500">
        Generated {{ formatDate(store.reports?.generated_at) }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const store = useDataBreachStore();

const byStatus = computed(() => store.reports?.statistics?.by_status || {});

const statCards = computed(() => {
  const s = store.reports?.statistics || {};
  return [
    { label: 'Total', value: s.total ?? 0 },
    { label: 'Active', value: s.active ?? 0 },
    { label: 'Closed', value: s.closed ?? 0 },
    { label: 'Regulator overdue', value: s.regulator_overdue ?? 0 },
  ];
});

onMounted(() => {
  store.fetchReports();
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
