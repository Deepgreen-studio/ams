<template>
  <div>
    <!-- <PageHeader
      title="Audit reports"
      description="Compliance module activity log results — events, volume trends, and recent audit trail."
    /> -->
    <ComplianceSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'audit')"
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

    <div v-if="store.loading && !store.audit" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.audit">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Audit events</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">
            {{ store.audit.summary?.total ?? 0 }}
          </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 sm:col-span-2">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Period</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">
            {{ store.audit.period?.from }} → {{ store.audit.period?.to }}
          </p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Audit event volume"
          :labels="store.audit.trends?.labels || []"
          :series="[{ key: 'events', label: 'Events', values: store.audit.trends?.events || [] }]"
        />
        <SimpleBarChart title="By event" :data="store.audit.by_event || {}" />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Recent audit results</h3>
        <EmptyState
          v-if="!(store.audit.recent || []).length"
          title="No audit events"
          description="Compliance activity appears here after module actions."
        />
        <ul v-else class="divide-y divide-slate-100 text-sm">
          <li v-for="item in store.audit.recent" :key="item.id" class="py-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <p class="font-medium text-slate-900">{{ item.description }}</p>
                <p class="text-xs text-slate-500">
                  {{ item.event || 'event' }}
                  <span v-if="item.causer?.full_name"> · {{ item.causer.full_name }}</span>
                </p>
              </div>
              <span class="text-xs text-slate-400">{{ formatDate(item.created_at) }}</span>
            </div>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchAudit();
}

onMounted(() => store.fetchAudit());
</script>
