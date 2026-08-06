<template>
  <div>
    <PageHeader
      :title="store.currentReport?.name || 'Report Designer'"
      description="Configure columns, filters, sorting, grouping, preview, export, and scheduling."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'analytics.reports' }"
          class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="onSave"
        >
          Save designer
        </button>
      </template>
    </PageHeader>

    <AnalyticsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !form.name" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
      Loading designer…
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-12">
      <div class="space-y-4 xl:col-span-4">
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Basics</h3>
          <div class="mt-3 space-y-3">
            <label class="block text-sm text-slate-600">
              Name
              <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block text-sm text-slate-600">
              Type
              <select v-model="form.report_type" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option v-for="t in store.reportTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </label>
            <label class="block text-sm text-slate-600">
              Visibility
              <select v-model="form.visibility" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="personal">Personal</option>
                <option value="company">Company</option>
                <option value="shared">Shared</option>
              </select>
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="form.is_saved" type="checkbox" class="rounded border-slate-300" />
              Saved report
            </label>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
          <div class="mt-3 grid grid-cols-2 gap-3">
            <label class="block text-sm text-slate-600">
              From
              <input v-model="form.filters.from" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block text-sm text-slate-600">
              To
              <input v-model="form.filters.to" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="col-span-2 block text-sm text-slate-600">
              Category
              <input v-model="form.filters.category" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="business" />
            </label>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Sorting & grouping</h3>
          <div class="mt-3 space-y-3">
            <label class="block text-sm text-slate-600">
              Sort field
              <select v-model="form.sorting.field" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option v-for="col in store.columnCatalog" :key="col.key" :value="col.key">{{ col.label }}</option>
              </select>
            </label>
            <label class="block text-sm text-slate-600">
              Direction
              <select v-model="form.sorting.direction" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="desc">Descending</option>
                <option value="asc">Ascending</option>
              </select>
            </label>
            <label class="block text-sm text-slate-600">
              Group by
              <select v-model="groupField" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">None</option>
                <option v-for="col in store.columnCatalog" :key="`g-${col.key}`" :value="col.key">{{ col.label }}</option>
              </select>
            </label>
            <label class="block text-sm text-slate-600">
              Aggregate
              <select v-model="form.grouping.aggregate" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="count">Count</option>
                <option value="sum">Sum</option>
                <option value="avg">Average</option>
              </select>
            </label>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Columns</h3>
          <div class="mt-3 max-h-56 space-y-2 overflow-y-auto">
            <label
              v-for="col in store.columnCatalog"
              :key="col.key"
              class="flex items-center gap-2 text-sm text-slate-700"
            >
              <input
                type="checkbox"
                class="rounded border-slate-300"
                :checked="selectedColumnKeys.includes(col.key)"
                @change="toggleColumn(col)"
              />
              {{ col.label }}
            </label>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Schedule</h3>
          <div class="mt-3 space-y-3">
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="schedule.enabled" type="checkbox" class="rounded border-slate-300" />
              Enable scheduled generation
            </label>
            <label class="block text-sm text-slate-600">
              Cron
              <input v-model="schedule.cron" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="0 7 * * *" />
            </label>
            <label class="block text-sm text-slate-600">
              Format
              <select v-model="schedule.format" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option v-for="f in store.reportFormats" :key="f" :value="f">{{ f.toUpperCase() }}</option>
              </select>
            </label>
            <button
              type="button"
              class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-60"
              :disabled="store.saving"
              @click="onSchedule"
            >
              Save schedule
            </button>
          </div>
        </section>
      </div>

      <div class="space-y-4 xl:col-span-8">
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Preview & export</h3>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700" :disabled="store.saving" @click="onPreview">
                Preview
              </button>
              <button
                v-for="fmt in ['csv', 'excel', 'pdf', 'print']"
                :key="fmt"
                type="button"
                class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                :disabled="store.saving"
                @click="onExport(fmt)"
              >
                {{ fmt.toUpperCase() }}
              </button>
            </div>
          </div>

          <div v-if="store.reportPreview" class="mt-4 overflow-x-auto">
            <p class="mb-2 text-xs text-slate-500">
              {{ store.reportPreview.meta?.row_count ?? 0 }} rows · generated {{ store.reportPreview.meta?.generated_at }}
            </p>
            <table class="min-w-full text-left text-sm">
              <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                  <th v-for="col in store.reportPreview.columns" :key="col.key" class="px-3 py-2">{{ col.label }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in store.reportPreview.rows.slice(0, 50)" :key="idx" class="border-t border-slate-100">
                  <td v-for="col in store.reportPreview.columns" :key="col.key" class="px-3 py-2 text-slate-700">
                    {{ row[col.key] }}
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="store.reportPreview.groups?.length" class="mt-4">
              <h4 class="mb-2 text-sm font-semibold text-slate-800">Groups</h4>
              <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="group in store.reportPreview.groups"
                  :key="group.group_key"
                  class="rounded-lg border border-slate-200 px-3 py-2 text-sm"
                >
                  <p class="font-medium text-slate-900">{{ group.group_key }}</p>
                  <p class="text-slate-500">count {{ group.count }} · agg {{ group.aggregate_value }}</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="mt-4 text-sm text-slate-500">Run preview to inspect rows, groups, and chart payload.</p>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Run history</h3>
          <table class="mt-3 min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Format</th>
                <th class="px-3 py-2">Rows</th>
                <th class="px-3 py-2">When</th>
                <th class="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!store.reportRuns.length">
                <td colspan="5" class="px-3 py-6 text-center text-slate-500">No runs yet.</td>
              </tr>
              <tr v-for="run in store.reportRuns" :key="run.uuid" class="border-b border-slate-100">
                <td class="px-3 py-2 capitalize">{{ run.status }}</td>
                <td class="px-3 py-2 uppercase">{{ run.format }}</td>
                <td class="px-3 py-2">{{ run.row_count }}</td>
                <td class="px-3 py-2 text-slate-500">{{ run.completed_at || run.created_at }}</td>
                <td class="px-3 py-2">
                  <button
                    v-if="run.status === 'completed'"
                    type="button"
                    class="text-sm font-medium text-brand-700 hover:underline"
                    @click="onDownload(run)"
                  >
                    Download
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const route = useRoute();
const store = useEnterpriseAnalyticsStore();

const form = reactive({
  name: '',
  description: '',
  report_type: 'tabular',
  status: 'active',
  visibility: 'personal',
  is_saved: true,
  columns: [],
  filters: { from: '', to: '', category: '' },
  sorting: { field: 'occurred_at', direction: 'desc' },
  grouping: { fields: [], aggregate: 'count' },
  chart_config: { type: 'bar' },
  format_defaults: { format: 'csv' },
});

const schedule = reactive({
  enabled: false,
  cron: '0 7 * * *',
  format: 'csv',
  timezone: 'UTC',
});

const selectedColumnKeys = computed(() => (form.columns || []).map((c) => c.key));

const groupField = computed({
  get: () => form.grouping?.fields?.[0] || '',
  set: (value) => {
    form.grouping.fields = value ? [value] : [];
  },
});

function hydrate(report) {
  if (!report) return;
  form.name = report.name || '';
  form.description = report.description || '';
  form.report_type = report.report_type || 'tabular';
  form.status = report.status || 'active';
  form.visibility = report.visibility || 'personal';
  form.is_saved = !!report.is_saved;
  form.columns = report.columns?.length
    ? report.columns
    : (store.columnCatalog || []).slice(0, 5).map((c) => ({ key: c.key, label: c.label }));
  form.filters = {
    from: report.filters?.from || '',
    to: report.filters?.to || '',
    category: report.filters?.category || '',
  };
  form.sorting = {
    field: report.sorting?.field || 'occurred_at',
    direction: report.sorting?.direction || 'desc',
  };
  form.grouping = {
    fields: report.grouping?.fields || report.grouping?.by || [],
    aggregate: report.grouping?.aggregate || 'count',
  };
  form.chart_config = report.chart_config || { type: 'bar' };
  form.format_defaults = report.format_defaults || { format: 'csv' };
  schedule.enabled = !!report.schedule_config?.enabled || !!report.is_scheduled;
  schedule.cron = report.schedule_config?.cron || '0 7 * * *';
  schedule.format = report.schedule_config?.format || 'csv';
  schedule.timezone = report.schedule_config?.timezone || 'UTC';
}

function toggleColumn(col) {
  const exists = form.columns.find((c) => c.key === col.key);
  if (exists) {
    form.columns = form.columns.filter((c) => c.key !== col.key);
  } else {
    form.columns = [...form.columns, { key: col.key, label: col.label }];
  }
}

function designerPayload() {
  return {
    name: form.name,
    description: form.description,
    report_type: form.report_type,
    status: form.status,
    visibility: form.visibility,
    is_saved: form.is_saved,
    columns: form.columns,
    filters: form.filters,
    sorting: form.sorting,
    grouping: form.grouping.fields?.length ? form.grouping : null,
    chart_config: form.report_type === 'chart' || form.chart_config ? form.chart_config : null,
    format_defaults: form.format_defaults,
  };
}

async function onSave() {
  await store.saveReportDesigner(route.params.uuid, designerPayload());
}

async function onPreview() {
  await store.previewReport(route.params.uuid, form.filters);
}

async function onExport(format) {
  await store.runReport(route.params.uuid, { format, ...form.filters });
  await store.fetchReportRuns(route.params.uuid);
}

async function onSchedule() {
  await store.scheduleReport(route.params.uuid, { ...schedule });
}

async function onDownload(run) {
  await store.downloadReportRun(route.params.uuid, run.uuid, run.file_name || `report.${run.format}`);
}

watch(
  () => store.currentReport,
  (report) => hydrate(report),
  { immediate: true }
);

onMounted(async () => {
  await store.fetchReport(route.params.uuid);
  await store.fetchReportRuns(route.params.uuid);
});
</script>
