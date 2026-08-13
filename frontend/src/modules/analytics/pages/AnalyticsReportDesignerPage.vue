<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.reports' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ArrowLeftIcon class="h-4 w-4" />
        Back
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="onSave"
      >
        <CheckIcon class="h-4 w-4" />
        Save designer
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <div v-if="store.loading && !form.name" class="space-y-4">
      <div class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="h-64 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-12">
      <div class="space-y-4 xl:col-span-4">
        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold text-slate-900">Basics</h3>
          <div class="mt-3 space-y-3">
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
              <input v-model="form.name" type="text" class="input" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
              <SelectBox v-model="form.report_type" :options="reportTypeOptions" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Visibility</label>
              <SelectBox v-model="form.visibility" :options="visibilityOptions" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input
                v-model="form.is_saved"
                type="checkbox"
                class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
              />
              Saved report
            </label>
          </div>
        </section>

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
          <div class="mt-3 grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">From</label>
              <input v-model="form.filters.from" type="date" class="input" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">To</label>
              <input v-model="form.filters.to" type="date" class="input" />
            </div>
            <div class="col-span-2">
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
              <input v-model="form.filters.category" type="text" class="input" placeholder="business" />
            </div>
          </div>
        </section>

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold text-slate-900">Sorting & grouping</h3>
          <div class="mt-3 space-y-3">
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Sort field</label>
              <SelectBox v-model="form.sorting.field" :options="columnOptions" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Direction</label>
              <SelectBox v-model="form.sorting.direction" :options="directionOptions" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Group by</label>
              <SelectBox v-model="groupField" :options="groupFieldOptions" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Aggregate</label>
              <SelectBox v-model="form.grouping.aggregate" :options="aggregateOptions" />
            </div>
          </div>
        </section>

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold text-slate-900">Columns</h3>
          <div class="mt-3 max-h-56 space-y-2 overflow-y-auto">
            <label
              v-for="col in store.columnCatalog"
              :key="col.key"
              class="flex items-center gap-2 text-sm text-slate-700"
            >
              <input
                type="checkbox"
                class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
                :checked="selectedColumnKeys.includes(col.key)"
                @change="toggleColumn(col)"
              />
              {{ col.label }}
            </label>
          </div>
        </section>

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold text-slate-900">Schedule</h3>
          <div class="mt-3 space-y-3">
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input
                v-model="schedule.enabled"
                type="checkbox"
                class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
              />
              Enable scheduled generation
            </label>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Cron</label>
              <input v-model="schedule.cron" type="text" class="input" placeholder="0 7 * * *" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Format</label>
              <SelectBox v-model="schedule.format" :options="formatOptions" />
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
              @click="onSchedule"
            >
              Save schedule
            </button>
          </div>
        </section>
      </div>

      <div class="space-y-4 xl:col-span-8">
        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Preview & export</h3>
            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="inline-flex items-center rounded-[12px] border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
                :disabled="store.saving"
                @click="onPreview"
              >
                Preview
              </button>
              <button
                v-for="fmt in ['csv', 'excel', 'pdf', 'print']"
                :key="fmt"
                type="button"
                class="inline-flex items-center rounded-[12px] bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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
            <div class="overflow-hidden rounded-[12px] ring-1 ring-zinc-100">
              <table class="min-w-full text-left text-sm">
                <thead>
                  <tr class="border-b border-zinc-100">
                    <th
                      v-for="col in store.reportPreview.columns"
                      :key="col.key"
                      class="px-5 py-3 text-sm font-semibold text-zinc-500"
                    >
                      {{ col.label }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(row, idx) in store.reportPreview.rows.slice(0, 50)"
                    :key="idx"
                    class="border-b border-zinc-50 last:border-0 hover:bg-zinc-50/80"
                  >
                    <td
                      v-for="col in store.reportPreview.columns"
                      :key="col.key"
                      class="px-5 py-4 text-slate-700"
                    >
                      {{ row[col.key] }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="store.reportPreview.groups?.length" class="mt-4">
              <h4 class="mb-2 text-sm font-semibold text-slate-800">Groups</h4>
              <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="group in store.reportPreview.groups"
                  :key="group.group_key"
                  class="rounded-[12px] px-3 py-2 text-sm ring-1 ring-zinc-100"
                >
                  <p class="font-medium text-slate-900">{{ group.group_key }}</p>
                  <p class="text-slate-500">count {{ group.count }} · agg {{ group.aggregate_value }}</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="mt-4 text-sm text-slate-500">Run preview to inspect rows, groups, and chart payload.</p>
        </section>

        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Run history</h3>
          </div>
          <EmptyState
            v-if="!store.reportRuns.length"
            title="No runs yet"
            description="Preview or export this report to generate a run history."
          />
          <div v-else class="overflow-x-auto px-3">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="border-b border-zinc-100">
                  <th class="px-5 py-3 text-sm font-semibold text-zinc-500">Status</th>
                  <th class="px-5 py-3 text-sm font-semibold text-zinc-500">Format</th>
                  <th class="px-5 py-3 text-sm font-semibold text-zinc-500">Rows</th>
                  <th class="px-5 py-3 text-sm font-semibold text-zinc-500">When</th>
                  <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="run in store.reportRuns"
                  :key="run.uuid"
                  class="border-b border-zinc-50 last:border-0 hover:bg-zinc-50/80"
                >
                  <td class="px-5 py-4 capitalize text-slate-700">{{ run.status }}</td>
                  <td class="px-5 py-4 uppercase text-slate-700">{{ run.format }}</td>
                  <td class="px-5 py-4 text-slate-700">{{ run.row_count }}</td>
                  <td class="px-5 py-4 text-slate-500">{{ run.completed_at || run.created_at }}</td>
                  <td class="px-5 py-4 text-right">
                    <button
                      v-if="run.status === 'completed'"
                      type="button"
                      class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                      @click="onDownload(run)"
                    >
                      Download
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const route = useRoute();
const store = useEnterpriseAnalyticsStore();
const toast = useToast();

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

const visibilityOptions = [
  { value: 'personal', label: 'Personal' },
  { value: 'company', label: 'Company' },
  { value: 'shared', label: 'Shared' },
];

const directionOptions = [
  { value: 'desc', label: 'Descending' },
  { value: 'asc', label: 'Ascending' },
];

const aggregateOptions = [
  { value: 'count', label: 'Count' },
  { value: 'sum', label: 'Sum' },
  { value: 'avg', label: 'Average' },
];

const reportTypeOptions = computed(() =>
  store.reportTypes.length
    ? store.reportTypes
    : [
        { value: 'tabular', label: 'Tabular' },
        { value: 'chart', label: 'Chart' },
        { value: 'grouped', label: 'Grouped' },
        { value: 'scheduled', label: 'Scheduled' },
      ]
);

const columnOptions = computed(() =>
  (store.columnCatalog || []).map((col) => ({ value: col.key, label: col.label }))
);

const groupFieldOptions = computed(() => [{ value: '', label: 'None' }, ...columnOptions.value]);

const formatOptions = computed(() =>
  (store.reportFormats || ['csv', 'excel', 'pdf', 'print']).map((f) => ({
    value: f,
    label: String(f).toUpperCase(),
  }))
);

const selectedColumnKeys = computed(() => (form.columns || []).map((c) => c.key));

const groupField = computed({
  get: () => form.grouping?.fields?.[0] || '',
  set: (value) => {
    form.grouping.fields = value ? [value] : [];
  },
});

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

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
  store.successMessage = null;
  store.error = null;
  await store.fetchReport(route.params.uuid);
  await store.fetchReportRuns(route.params.uuid);
});
</script>
