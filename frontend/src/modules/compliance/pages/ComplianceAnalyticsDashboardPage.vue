<template>
  <div>
    <!-- <PageHeader
      title="Compliance Analytics"
      description="Cross-module KPIs for privacy, cases, risk, policies, consent, breaches, and audit activity."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.analytics.risks' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Risk charts
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.analytics.gdpr' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          GDPR report
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.analytics.risks' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Risk charts
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.analytics.gdpr' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          GDPR report
        </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'overview')"
    />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !store.dashboard" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.dashboard">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in kpiCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white px-4 py-3"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Operational volume"
          hint="Daily created records"
          :labels="store.dashboard.trends?.labels || []"
          :series="volumeSeries"
        />
        <SimpleLineChart
          title="Policy & consent activity"
          hint="Daily updates"
          :labels="store.dashboard.trends?.labels || []"
          :series="governanceSeries"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <SimpleBarChart title="Privacy by status" :data="store.dashboard.privacy?.by_status || {}" />
        <SimpleBarChart title="Cases by priority" :data="store.dashboard.cases?.by_priority || {}" />
        <SimpleBarChart title="Breaches by severity" :data="store.dashboard.breaches?.by_severity || {}" />
      </div>

      <div class="mt-4 flex flex-wrap gap-2">
        <RouterLink
          v-for="link in reportLinks"
          :key="link.name"
          :to="{ name: link.name }"
          class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();

const kpiCards = computed(() => {
  const k = store.dashboard?.kpis || {};
  return [
    { label: 'Privacy requests', value: k.privacy_requests ?? 0, hint: `${k.privacy_requests_open ?? 0} open` },
    {
      label: 'Avg resolution',
      value: `${k.average_resolution_hours ?? 0}h`,
      hint: 'Completed DSAR cycle time',
    },
    { label: 'Compliance cases', value: k.compliance_cases ?? 0, hint: `${k.compliance_cases_open ?? 0} open` },
    { label: 'Risk score', value: k.risk_score ?? 0, hint: `${k.open_risks ?? 0} open / ${k.closed_risks ?? 0} closed` },
    { label: 'Policy updates', value: k.policy_updates ?? 0 },
    { label: 'Consents granted', value: k.consent_granted ?? 0, hint: `${k.consent_withdrawn ?? 0} withdrawn` },
    { label: 'Data breaches', value: k.data_breaches ?? 0, hint: `${k.data_breaches_open ?? 0} open` },
    { label: 'Audit events', value: k.audit_events ?? 0 },
  ];
});

const volumeSeries = computed(() => [
  { key: 'privacy', label: 'Privacy requests', values: store.dashboard?.trends?.privacy_requests || [] },
  { key: 'cases', label: 'Cases', values: store.dashboard?.trends?.compliance_cases || [] },
  { key: 'breaches', label: 'Breaches', values: store.dashboard?.trends?.data_breaches || [] },
]);

const governanceSeries = computed(() => [
  { key: 'policies', label: 'Policy updates', values: store.dashboard?.trends?.policy_updates || [] },
  { key: 'consents', label: 'Consent events', values: store.dashboard?.trends?.consent_events || [] },
  { key: 'audit', label: 'Audit events', values: store.dashboard?.trends?.audit_events || [] },
]);

const reportLinks = [
  { name: 'compliance.analytics.risks', label: 'Risk charts' },
  { name: 'compliance.analytics.gdpr', label: 'GDPR reports' },
  { name: 'compliance.analytics.consent', label: 'Consent reports' },
  { name: 'compliance.analytics.audit', label: 'Audit reports' },
];

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchDashboard();
}

onMounted(() => store.fetchDashboard());
</script>
