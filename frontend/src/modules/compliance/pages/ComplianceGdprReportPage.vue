<template>
  <div>
    <!-- <PageHeader
      title="GDPR reports"
      description="Privacy request volume, resolution time, breaches, DPIA status, and related cases."
    /> -->
    <ComplianceSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'gdpr')"
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

    <div v-if="store.loading && !store.gdpr" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.gdpr">
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
          title="GDPR activity trends"
          :labels="store.gdpr.trends?.labels || []"
          :series="trendSeries"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <SimpleBarChart title="Privacy by status" :data="store.gdpr.privacy_requests?.by_status || {}" />
        <SimpleBarChart title="Privacy by type" :data="store.gdpr.privacy_requests?.by_type || {}" />
        <SimpleBarChart title="DPIA by status" :data="store.gdpr.dpia?.by_status || {}" />
        <SimpleBarChart title="Breaches by status" :data="store.gdpr.data_breaches?.by_status || {}" />
        <SimpleBarChart title="Breaches by severity" :data="store.gdpr.data_breaches?.by_severity || {}" />
        <SimpleBarChart title="Cases by type" :data="store.gdpr.cases?.by_type || {}" />
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

const cards = computed(() => [
  { label: 'Privacy requests', value: store.gdpr?.privacy_requests?.total ?? 0 },
  {
    label: 'Avg resolution (h)',
    value: store.gdpr?.privacy_requests?.average_resolution_hours ?? 0,
  },
  { label: 'Data breaches', value: store.gdpr?.data_breaches?.total ?? 0 },
  { label: 'DPIAs', value: store.gdpr?.dpia?.total ?? 0 },
]);

const trendSeries = computed(() => [
  { key: 'privacy', label: 'Privacy', values: store.gdpr?.trends?.privacy_requests || [] },
  { key: 'breaches', label: 'Breaches', values: store.gdpr?.trends?.data_breaches || [] },
  { key: 'dpia', label: 'DPIA', values: store.gdpr?.trends?.dpia || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchGdpr();
}

onMounted(() => store.fetchGdpr());
</script>
