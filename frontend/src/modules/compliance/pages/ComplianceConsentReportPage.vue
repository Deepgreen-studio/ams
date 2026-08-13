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
      <p class="text-sm font-medium text-slate-900">Unable to load consent report</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading grant and withdrawal metrics again.</p>
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
          title="Consent grant vs withdraw"
          hint="Daily preference events"
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
import { RouterLink } from 'vue-router';
import {
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  NoSymbolIcon,
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

const hasData = computed(() => Boolean(store.consent));

const cards = computed(() => {
  const s = store.consent?.summary || {};
  const granted = s.granted ?? 0;
  const withdrawn = s.withdrawn ?? 0;
  const pendingExpired = (s.pending ?? 0) + (s.expired ?? 0);
  const total = s.total ?? 0;

  return [
    {
      label: 'Total events',
      value: total,
      hint: 'All consent activity in period',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Granted',
      value: granted,
      hint: granted ? 'Subjects opted in' : 'No grants in period',
      icon: CheckCircleIcon,
      iconBg: granted ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: granted ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Withdrawn',
      value: withdrawn,
      hint: withdrawn ? 'Opt-outs recorded' : 'No withdrawals',
      icon: NoSymbolIcon,
      iconBg: withdrawn ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: withdrawn ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Pending / expired',
      value: pendingExpired,
      hint: pendingExpired ? 'Needs recapture or confirmation' : 'Nothing outstanding',
      icon: ClockIcon,
      iconBg: pendingExpired ? 'bg-amber-50' : 'bg-emerald-50',
      iconColor: pendingExpired ? 'text-amber-500' : 'text-emerald-500',
    },
  ];
});

const trendSeries = computed(() => [
  { key: 'granted', label: 'Granted', values: store.consent?.trends?.granted || [] },
  { key: 'withdrawn', label: 'Withdrawn', values: store.consent?.trends?.withdrawn || [] },
]);

async function reload() {
  try {
    await store.fetchConsent();
  } catch {
    toast.error(store.error || 'Unable to load consent report');
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
    const result = await store.exportReport(format, 'consent');
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
