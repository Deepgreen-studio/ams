<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.analytics.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Overview
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

    <div v-if="store.loading && !hasData" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasData"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load GDPR report</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading privacy and DPIA metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="hasData">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in cards"
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

      <div class="mb-4">
        <SimpleLineChart
          title="GDPR activity trends"
          hint="Privacy, breaches, and DPIA volume"
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
import { RouterLink } from 'vue-router';
import {
  ClockIcon,
  DocumentMagnifyingGlassIcon,
  IdentificationIcon,
  ShieldExclamationIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();
const toast = useToast();

const hasData = computed(() => Boolean(store.gdpr));

const cards = computed(() => {
  const privacy = store.gdpr?.privacy_requests || {};
  const breaches = store.gdpr?.data_breaches || {};
  const dpia = store.gdpr?.dpia || {};
  const privacyTotal = privacy.total ?? 0;
  const breachTotal = breaches.total ?? 0;
  const dpiaTotal = dpia.total ?? 0;

  return [
    {
      label: 'Privacy requests',
      value: privacyTotal,
      hint: 'DSARs in the selected period',
      icon: IdentificationIcon,
      iconBg: privacyTotal ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: privacyTotal ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Avg resolution',
      value: `${privacy.average_resolution_hours ?? 0}h`,
      hint: 'Completed request cycle time',
      icon: ClockIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-500',
    },
    {
      label: 'Data breaches',
      value: breachTotal,
      hint: 'Incidents in this period',
      icon: ShieldExclamationIcon,
      iconBg: breachTotal ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: breachTotal ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'DPIAs',
      value: dpiaTotal,
      hint: 'Assessments in this period',
      icon: DocumentMagnifyingGlassIcon,
      iconBg: dpiaTotal ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: dpiaTotal ? 'text-brand-500' : 'text-slate-500',
    },
  ];
});

const trendSeries = computed(() => [
  { key: 'privacy', label: 'Privacy', values: store.gdpr?.trends?.privacy_requests || [] },
  { key: 'breaches', label: 'Breaches', values: store.gdpr?.trends?.data_breaches || [] },
  { key: 'dpia', label: 'DPIA', values: store.gdpr?.trends?.dpia || [] },
]);

async function reload() {
  try {
    await store.fetchGdpr();
  } catch {
    toast.error(store.error || 'Unable to load GDPR report');
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
    const result = await store.exportReport(format, 'gdpr');
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
