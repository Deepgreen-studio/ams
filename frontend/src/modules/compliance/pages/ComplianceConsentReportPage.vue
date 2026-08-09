<template>
  <div>
    <!-- <PageHeader
      title="Consent reports"
      description="Granted, withdrawn, pending, and expired consent activity with source breakdown."
    /> -->
    <ComplianceSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'consent')"
    />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.consent" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.consent">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in cards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white px-4 py-3"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4">
        <SimpleLineChart
          title="Consent grant vs withdraw"
          :labels="store.consent.trends?.labels || []"
          :series="trendSeries"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By status" :data="store.consent.by_status || {}" />
        <SimpleBarChart title="By source" :data="store.consent.by_source || {}" />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();

const cards = computed(() => {
  const s = store.consent?.summary || {};
  return [
    { label: 'Total events', value: s.total ?? 0 },
    { label: 'Granted', value: s.granted ?? 0 },
    { label: 'Withdrawn', value: s.withdrawn ?? 0 },
    { label: 'Pending / expired', value: (s.pending ?? 0) + (s.expired ?? 0) },
  ];
});

const trendSeries = computed(() => [
  { key: 'granted', label: 'Granted', values: store.consent?.trends?.granted || [] },
  { key: 'withdrawn', label: 'Withdrawn', values: store.consent?.trends?.withdrawn || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchConsent();
}

onMounted(() => store.fetchConsent());
</script>
