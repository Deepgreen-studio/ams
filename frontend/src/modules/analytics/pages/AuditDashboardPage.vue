<template>
  <div>
    <PageHeader title="Audit Dashboard" description="Logins, permission/role changes, data exports, and deletions." />
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
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart title="Successful logins" :points="chartPoints(data.charts?.logins_success)" value-key="value" stroke="#0f766e" fill="#0f766e" />
        <SimpleLineChart title="Failed logins" :points="chartPoints(data.charts?.logins_failed)" value-key="value" stroke="#be123c" fill="#be123c" />
        <SimpleLineChart title="Permission changes" :points="chartPoints(data.charts?.permission_changes)" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
        <SimpleLineChart title="Role changes" :points="chartPoints(data.charts?.role_changes)" value-key="value" stroke="#0369a1" fill="#0369a1" />
        <SimpleLineChart title="Data exports" :points="chartPoints(data.charts?.data_exports)" value-key="value" stroke="#b45309" fill="#b45309" />
        <SimpleLineChart title="Data deletions" :points="chartPoints(data.charts?.data_deletions)" value-key="value" stroke="#9f1239" fill="#9f1239" />
      </div>

      <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Recent role / permission events</h3>
          <ul class="mt-3 max-h-80 space-y-2 overflow-y-auto">
            <li v-for="(row, idx) in data.recent_role_events || []" :key="idx" class="rounded-lg bg-slate-50 px-3 py-2 text-sm">
              <p class="font-medium text-slate-800">{{ row.title || row.event || 'Role event' }}</p>
              <p class="text-slate-500">{{ row.message || row.description }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ row.occurred_at }}</p>
            </li>
            <li v-if="!(data.recent_role_events || []).length" class="py-6 text-center text-sm text-slate-500">No role events.</li>
          </ul>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Recent audit actions</h3>
          <ul class="mt-3 max-h-80 space-y-2 overflow-y-auto">
            <li v-for="(row, idx) in data.recent_audit_actions || []" :key="idx" class="rounded-lg bg-slate-50 px-3 py-2 text-sm">
              <p class="font-medium text-slate-800">{{ row.action || row.title }}</p>
              <p class="text-slate-500">{{ row.module }} · {{ row.message || row.reason }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ row.occurred_at || row.created_at }}</p>
            </li>
            <li v-if="!(data.recent_audit_actions || []).length" class="py-6 text-center text-sm text-slate-500">No audit actions.</li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SecurityAnalyticsSubnav from '@/modules/analytics/components/SecurityAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const data = computed(() => store.audit);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const kpiCards = computed(() => [
  { label: 'Successful logins', value: data.value?.kpis?.logins_success ?? 0 },
  { label: 'Failed logins', value: data.value?.kpis?.logins_failed ?? 0 },
  { label: 'Permission changes', value: data.value?.kpis?.permission_changes ?? 0 },
  { label: 'Role changes', value: data.value?.kpis?.role_changes ?? 0 },
  { label: 'Data exports', value: data.value?.kpis?.data_exports ?? 0 },
  { label: 'Data deletions', value: data.value?.kpis?.data_deletions ?? 0 },
  { label: 'Audit actions', value: data.value?.kpis?.audit_actions ?? 0 },
]);

function chartPoints(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

async function load() {
  await store.fetchAudit({ ...filters });
}

onMounted(load);
</script>
