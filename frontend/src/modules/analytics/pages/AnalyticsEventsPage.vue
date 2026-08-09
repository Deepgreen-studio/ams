<template>
  <div>
    <!-- <PageHeader
      title="Analytics Events"
      description="Central event stream powering enterprise analytics across AMS domains."
    /> -->

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="store.categories"
      show-search
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.eventsSummary" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total events</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ formatNumber(store.eventsSummary.total) }}</p>
      </div>
      <div
        v-for="(count, category) in topCategories"
        :key="category"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 capitalize">{{ category }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ formatNumber(count) }}</p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Occurred</th>
            <th class="px-4 py-3 font-medium">Category</th>
            <th class="px-4 py-3 font-medium">Event</th>
            <th class="px-4 py-3 font-medium">Source</th>
            <th class="px-4 py-3 font-medium">Subject</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading events…</td>
          </tr>
          <tr v-else-if="!store.events.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No analytics events in this period.</td>
          </tr>
          <tr v-for="event in store.events" :key="event.uuid" class="border-b border-slate-100">
            <td class="px-4 py-3 text-slate-700">{{ formatDate(event.occurred_at) }}</td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ event.category }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ event.event_name }}</td>
            <td class="px-4 py-3 text-slate-700">{{ event.event_source || '—' }}</td>
            <td class="px-4 py-3 text-slate-700">
              <span v-if="event.subject_type">{{ event.subject_type }}#{{ event.subject_id }}</span>
              <span v-else>—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();

const topCategories = computed(() => {
  const byCategory = store.eventsSummary?.by_category || {};
  return Object.fromEntries(
    Object.entries(byCategory)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 3)
  );
});

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchEvents();
  store.fetchEventsSummary();
}

onMounted(async () => {
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await Promise.all([store.fetchEvents(), store.fetchEventsSummary()]);
});
</script>
