<template>
  <div>
    <!-- <PageHeader
      title="Delivery Reports"
      description="Notifications sent, failed, delivery time, read rate, and click rate."
    /> -->
    <AnalyticsSubnav />
    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @export="(format) => store.exportReport(format, 'notifications')"
    />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.notifications" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="store.notifications">
      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Delivery volume"
          :labels="store.notifications.trends?.labels || []"
          :series="volumeSeries"
        />
        <SimpleLineChart
          title="Engagement"
          hint="Reads and clicks"
          :labels="store.notifications.trends?.labels || []"
          :series="engagementSeries"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By channel" :data="store.notifications.by_channel || {}" />
        <SimpleBarChart title="By status" :data="store.notifications.by_status || {}" />
      </div>

      <div class="mt-4 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Top events</h2>
        <ul class="divide-y divide-slate-100">
          <li v-if="!(store.notifications.top_events || []).length" class="py-6 text-center text-sm text-slate-500">No events.</li>
          <li
            v-for="row in store.notifications.top_events || []"
            :key="row.event_key"
            class="flex items-center justify-between py-3 text-sm"
          >
            <span class="text-slate-700">{{ row.event_key }}</span>
            <span class="font-medium text-slate-900">{{ row.total }}</span>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();

const cards = computed(() => [
  { label: 'Sent', value: store.notifications?.sent ?? 0 },
  { label: 'Failed', value: store.notifications?.failed ?? 0 },
  { label: 'Avg delivery (s)', value: store.notifications?.avg_delivery_seconds ?? 0 },
  { label: 'Read rate', value: `${store.notifications?.read_rate ?? 0}%` },
  { label: 'Click rate', value: `${store.notifications?.click_rate ?? 0}%` },
  { label: 'Delivery success', value: `${store.notifications?.delivery_success_rate ?? 0}%` },
]);

const volumeSeries = computed(() => [
  { key: 'sent', label: 'Sent', values: store.notifications?.trends?.sent || [] },
  { key: 'failed', label: 'Failed', values: store.notifications?.trends?.failed || [] },
]);

const engagementSeries = computed(() => [
  { key: 'reads', label: 'Reads', values: store.notifications?.trends?.reads || [] },
  { key: 'clicks', label: 'Clicks', values: store.notifications?.trends?.clicks || [] },
]);

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchNotifications();
}

onMounted(() => store.fetchNotifications());
</script>
