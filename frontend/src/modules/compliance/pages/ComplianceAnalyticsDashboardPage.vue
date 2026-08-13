<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.analytics.risks' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ChartBarIcon class="h-4 w-4" />
        Risk charts
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.analytics.gdpr' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <ShieldCheckIcon class="h-4 w-4" />
        GDPR report
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <AnalyticsFilterBar
      :model-value="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onReset"
      @export="onExport"
    />

    <div v-if="store.loading && !hasDashboard" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasDashboard"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load analytics overview</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading compliance KPIs again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="hasDashboard">
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in kpiCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
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
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ChartBarIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  FolderIcon,
  IdentificationIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();
const toast = useToast();

const hasDashboard = computed(() => Boolean(store.dashboard));
const kpis = computed(() => store.dashboard?.kpis || {});

const kpiCards = computed(() => {
  const k = kpis.value;
  const privacyOpen = k.privacy_requests_open ?? 0;
  const casesOpen = k.compliance_cases_open ?? 0;
  const openRisks = k.open_risks ?? 0;
  const withdrawn = k.consent_withdrawn ?? 0;
  const breachesOpen = k.data_breaches_open ?? 0;
  const granted = k.consent_granted ?? 0;
  const auditEvents = k.audit_events ?? 0;
  const policyUpdates = k.policy_updates ?? 0;

  return [
    {
      label: 'Privacy requests',
      value: k.privacy_requests ?? 0,
      hint: `${privacyOpen} open`,
      icon: IdentificationIcon,
      iconBg: privacyOpen ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: privacyOpen ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Avg resolution',
      value: `${k.average_resolution_hours ?? 0}h`,
      hint: 'Completed DSAR cycle time',
      icon: ClockIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-500',
    },
    {
      label: 'Compliance cases',
      value: k.compliance_cases ?? 0,
      hint: `${casesOpen} open`,
      icon: FolderIcon,
      iconBg: casesOpen ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: casesOpen ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Risk score',
      value: k.risk_score ?? 0,
      hint: `${openRisks} open / ${k.closed_risks ?? 0} closed`,
      icon: ExclamationTriangleIcon,
      iconBg: openRisks ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: openRisks ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Policy updates',
      value: policyUpdates,
      hint: policyUpdates ? 'Documents changed in period' : 'No policy changes',
      icon: DocumentTextIcon,
      iconBg: policyUpdates ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: policyUpdates ? 'text-brand-500' : 'text-slate-500',
    },
    {
      label: 'Consents granted',
      value: granted,
      hint: `${withdrawn} withdrawn`,
      icon: CheckCircleIcon,
      iconBg: granted ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: granted ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Data breaches',
      value: k.data_breaches ?? 0,
      hint: `${breachesOpen} open`,
      icon: ShieldExclamationIcon,
      iconBg: breachesOpen ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: breachesOpen ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Audit events',
      value: auditEvents,
      hint: auditEvents ? 'Logged in this period' : 'No audit activity',
      icon: ClipboardDocumentListIcon,
      iconBg: auditEvents ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: auditEvents ? 'text-violet-500' : 'text-slate-500',
    },
  ];
});

const healthMessage = computed(() => {
  const k = kpis.value;
  const breachesOpen = k.data_breaches_open ?? 0;
  const privacyOpen = k.privacy_requests_open ?? 0;
  const casesOpen = k.compliance_cases_open ?? 0;
  const openRisks = k.open_risks ?? 0;

  if (breachesOpen > 0) {
    return `${breachesOpen} open data breach${breachesOpen === 1 ? '' : 'es'} in the selected period.`;
  }
  if (privacyOpen > 0) {
    return `${privacyOpen} privacy request${privacyOpen === 1 ? '' : 's'} still open.`;
  }
  if (casesOpen > 0) {
    return `${casesOpen} compliance case${casesOpen === 1 ? '' : 's'} still open.`;
  }
  if (openRisks > 0) {
    return `${openRisks} open risk${openRisks === 1 ? '' : 's'} on the register.`;
  }
  return 'Compliance posture is healthy for the selected period.';
});

const healthTone = computed(() => {
  const k = kpis.value;
  if ((k.data_breaches_open ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((k.privacy_requests_open ?? 0) > 0 || (k.compliance_cases_open ?? 0) > 0) {
    return 'bg-amber-50 text-amber-800';
  }
  if ((k.open_risks ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const k = kpis.value;
  if ((k.data_breaches_open ?? 0) > 0) return ShieldExclamationIcon;
  if ((k.privacy_requests_open ?? 0) > 0 || (k.compliance_cases_open ?? 0) > 0 || (k.open_risks ?? 0) > 0) {
    return ExclamationTriangleIcon;
  }
  return ShieldCheckIcon;
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

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load compliance analytics dashboard');
    store.error = null;
  }
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  reload();
}

function onReset() {
  store.resetFilters();
  reload();
}

async function onExport(format) {
  try {
    const result = await store.exportReport(format, 'overview');
    if (result === 'pdf-ready') {
      toast.info(store.successMessage || 'PDF export is architecture-ready.');
    } else {
      toast.success(store.successMessage || 'Export downloaded.');
    }
    store.successMessage = null;
  } catch {
    toast.error(store.error || 'Unable to export analytics');
    store.error = null;
  }
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});
</script>
