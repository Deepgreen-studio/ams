<template>
  <div>
    <!-- <PageHeader title="Export Security Report" description="Download JSON or CSV security analytics reports.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="store.loading"
          @click="loadJson"
        >
          Generate JSON
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="downloading"
          @click="downloadCsv"
        >
          Download CSV
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button
          type="button"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="store.loading"
          @click="loadJson"
        >
          Generate JSON
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="downloading"
          @click="downloadCsv"
        >
          Download CSV
        </button>
    </Teleport>
    <AnalyticsSubnav />
    <SecurityAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        From
        <input v-model="filters.from" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        To
        <input v-model="filters.to" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700" @click="loadJson">
        Refresh
      </button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !report" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="report">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-900">Report preview</h3>
          <p class="text-xs text-slate-400">Generated {{ formatTime(report.generated_at) }}</p>
        </div>
        <pre class="max-h-[480px] overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{{ previewJson }}</pre>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SecurityAnalyticsSubnav from '@/modules/analytics/components/SecurityAnalyticsSubnav.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

const store = useSecurityAnalyticsStore();
const report = computed(() => store.exportReport);
const downloading = ref(false);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const kpiCards = computed(() => [
  { label: 'Failed logins', value: report.value?.overview?.kpis?.logins_failed ?? 0 },
  { label: 'Security events', value: report.value?.overview?.kpis?.security_events ?? 0 },
  { label: 'GDPR requests', value: report.value?.overview?.kpis?.gdpr_requests ?? 0 },
  { label: 'Risk score', value: report.value?.overview?.kpis?.risk_score ?? 0 },
  { label: 'Timeline events', value: (report.value?.timeline || []).length },
]);

const previewJson = computed(() => JSON.stringify(report.value, null, 2));

function formatTime(value) {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

async function loadJson() {
  await store.fetchExport({ ...filters });
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
  } finally {
    downloading.value = false;
  }
}

onMounted(loadJson);
</script>
