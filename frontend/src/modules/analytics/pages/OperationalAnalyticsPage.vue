<template>
  <div>
    <PageHeader
      title="Operational Analytics"
      description="Notification delivery, automation executions, workflow outcomes, and AI usage in one place."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'analytics.delivery' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Delivery reports
        </RouterLink>
        <RouterLink
          :to="{ name: 'analytics.automation' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Automation reports
        </RouterLink>
      </template>
    </PageHeader>

    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'overview')"
    />

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !store.dashboard" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.dashboard">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Notification delivery"
          hint="Sent vs failed"
          :labels="store.dashboard.charts?.notifications_daily?.labels || []"
          :series="notificationSeries"
        />
        <SimpleLineChart
          title="Automation executions"
          hint="Success vs failed"
          :labels="store.dashboard.charts?.automation_daily?.labels || []"
          :series="automationSeries"
        />
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Workflow outcomes"
          hint="Completed vs failed"
          :labels="store.dashboard.charts?.workflow_daily?.labels || []"
          :series="workflowSeries"
        />
        <SimpleLineChart
          title="AI usage"
          hint="Daily requests"
          :labels="store.dashboard.charts?.ai_daily?.labels || []"
          :series="aiSeries"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <SimpleBarChart title="Notifications by channel" :data="store.dashboard.notifications?.by_channel || {}" />
        <SimpleBarChart title="Automation by status" :data="store.dashboard.automation?.by_status || {}" />
        <SimpleBarChart title="Workflows by status" :data="store.dashboard.workflows?.by_status || {}" />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();

const kpiCards = computed(() => [
  { label: 'Notifications sent', value: store.dashboard?.kpis?.notifications_sent ?? 0 },
  { label: 'Notifications failed', value: store.dashboard?.kpis?.notifications_failed ?? 0 },
  { label: 'Read rate', value: `${store.dashboard?.kpis?.read_rate ?? 0}%` },
  { label: 'Click rate', value: `${store.dashboard?.kpis?.click_rate ?? 0}%` },
  { label: 'Avg delivery (s)', value: store.dashboard?.kpis?.avg_delivery_seconds ?? 0 },
  { label: 'Automation executions', value: store.dashboard?.kpis?.automation_executions ?? 0 },
  { label: 'Workflow success', value: `${store.dashboard?.kpis?.workflow_success_rate ?? 0}%` },
  { label: 'AI requests', value: store.dashboard?.kpis?.ai_requests ?? 0, hint: `${store.dashboard?.kpis?.ai_tokens ?? 0} tokens` },
]);

const notificationSeries = computed(() => [
  { key: 'sent', label: 'Sent', values: store.dashboard?.charts?.notifications_daily?.sent || [] },
  { key: 'failed', label: 'Failed', values: store.dashboard?.charts?.notifications_daily?.failed || [] },
]);

const automationSeries = computed(() => [
  { key: 'success', label: 'Success', values: store.dashboard?.charts?.automation_daily?.success || [] },
  { key: 'failed', label: 'Failed', values: store.dashboard?.charts?.automation_daily?.failed || [] },
]);

const workflowSeries = computed(() => [
  { key: 'completed', label: 'Completed', values: store.dashboard?.charts?.workflow_daily?.completed || [] },
  { key: 'failed', label: 'Failed', values: store.dashboard?.charts?.workflow_daily?.failed || [] },
]);

const aiSeries = computed(() => [
  { key: 'requests', label: 'Requests', values: store.dashboard?.charts?.ai_daily?.requests || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchDashboard();
}

onMounted(() => {
  store.fetchDashboard();
});
</script>
