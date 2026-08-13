<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.loading"
        @click="loadJson"
      >
        <DocumentTextIcon class="h-4 w-4" />
        Generate JSON
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="downloading"
        @click="downloadCsv"
      >
        <ArrowDownTrayIcon class="h-4 w-4" />
        {{ downloading ? 'Downloading…' : 'Download CSV' }}
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="filters"
      :exporting="downloading"
      @apply="onApply"
      @reset="onApply"
      @export="onExport"
    />

    <div v-if="store.loading && !report" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 5" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !report"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load security report</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to generate a JSON preview for this date range.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="loadJson"
      >
        Retry
      </button>
    </div>

    <template v-else-if="report">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-6 py-5">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Report preview</h2>
            <p class="mt-0.5 text-xs text-slate-500">JSON payload generated {{ formatTime(report.generated_at) }}.</p>
          </div>
        </div>
        <pre class="max-h-[480px] overflow-auto bg-zinc-950 p-6 text-xs text-zinc-100">{{ previewJson }}</pre>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
  ArrowDownTrayIcon,
  ChartBarIcon,
  ClockIcon,
  DocumentTextIcon,
  LockClosedIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

const store = useSecurityAnalyticsStore();
const toast = useToast();
const report = computed(() => store.exportReport);
const downloading = ref(false);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const kpiCards = computed(() => {
  const kpis = report.value?.overview?.kpis || {};
  const failed = Number(kpis.logins_failed ?? 0);
  const events = Number(kpis.security_events ?? 0);
  const gdpr = Number(kpis.gdpr_requests ?? 0);
  const risk = Number(kpis.risk_score ?? 0);
  const timeline = (report.value?.timeline || []).length;

  return [
    {
      label: 'Failed logins',
      value: formatNumber(failed),
      hint: 'Authentication failures',
      icon: LockClosedIcon,
      iconBg: failed ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: failed ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Security events',
      value: formatNumber(events),
      hint: 'Recorded in this period',
      icon: ShieldExclamationIcon,
      iconBg: events ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: events ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'GDPR requests',
      value: formatNumber(gdpr),
      hint: 'Privacy intake',
      icon: DocumentTextIcon,
      iconBg: gdpr ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: gdpr ? 'text-brand-500' : 'text-slate-500',
    },
    {
      label: 'Risk score',
      value: formatNumber(risk),
      hint: 'Composite posture',
      icon: ChartBarIcon,
      iconBg: risk ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: risk ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'Timeline events',
      value: formatNumber(timeline),
      hint: 'Included in this export',
      icon: ClockIcon,
      iconBg: timeline ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: timeline ? 'text-sky-500' : 'text-slate-500',
    },
  ];
});

const previewJson = computed(() => JSON.stringify(report.value, null, 2));

watch(
  () => store.error,
  (message) => {
    if (!message || !store.exportReport) return;
    toast.error(message);
    store.error = null;
  },
);

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function formatTime(value) {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

function onApply(next) {
  Object.assign(filters, next);
  loadJson();
}

function loadJson() {
  store.fetchExport({ ...filters }).catch(() => {});
}

async function onExport(format) {
  if (format === 'pdf') {
    toast.error('PDF export is architecture-ready. Use CSV, or print this page.');
    return;
  }
  await downloadCsv();
}

async function downloadCsv() {
  downloading.value = true;
  try {
    const response = await analyticsService.securityExportCsv({ ...filters });
    const blob = new Blob([response.data], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `security-analytics-${filters.from}-${filters.to}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    toast.success('CSV export downloaded.');
  } catch (error) {
    toast.error(error?.response?.data?.message || error?.message || 'Unable to download CSV');
  } finally {
    downloading.value = false;
  }
}

onMounted(() => {
  store.error = null;
  loadJson();
});
</script>
