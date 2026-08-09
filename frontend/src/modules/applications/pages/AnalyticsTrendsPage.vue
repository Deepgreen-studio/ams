<template>
  <div>
    <!-- <PageHeader title="Trend analysis" description="Compare a metric against the previous period.">
      <template #actions>
        <select
          v-model="metric"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          @change="reload"
        >
          <option value="daily_users">Daily users</option>
          <option value="monthly_users">Monthly users</option>
          <option value="active_users">Active users</option>
          <option value="avg_session_seconds">Session time</option>
          <option value="installs">Installs</option>
          <option value="uninstalls">Uninstalls</option>
          <option value="retention_d1">Retention D1</option>
          <option value="retention_d7">Retention D7</option>
          <option value="retention_d30">Retention D30</option>
        </select>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <select
          v-model="metric"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          @change="reload"
        >
          <option value="daily_users">Daily users</option>
          <option value="monthly_users">Monthly users</option>
          <option value="active_users">Active users</option>
          <option value="avg_session_seconds">Session time</option>
          <option value="installs">Installs</option>
          <option value="uninstalls">Uninstalls</option>
          <option value="retention_d1">Retention D1</option>
          <option value="retention_d7">Retention D7</option>
          <option value="retention_d30">Retention D30</option>
        </select>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ analyticsStore.error }}
    </div>

    <div v-if="analyticsStore.trends" class="mb-4 grid gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Current total</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ formatNumber(analyticsStore.trends.current_total) }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Previous total</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ formatNumber(analyticsStore.trends.previous_total) }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Change</p>
        <p class="mt-1 text-2xl font-semibold" :class="changeClass">
          {{ analyticsStore.trends.change_percent }}%
        </p>
      </div>
    </div>

    <SimpleLineChart
      :title="`Trend: ${metric}`"
      :labels="analyticsStore.trends?.labels || []"
      :series="[{ key: 'metric', label: metric, values: analyticsStore.trends?.values || [] }]"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';

const route = useRoute();
const analyticsStore = useAnalyticsStore();
const metric = ref('daily_users');

const changeClass = computed(() => {
  const value = analyticsStore.trends?.change_percent ?? 0;
  if (value > 0) return 'text-emerald-700';
  if (value < 0) return 'text-rose-700';
  return 'text-slate-900';
});

onMounted(reload);

async function reload() {
  await analyticsStore.fetchTrends(route.params.id, { metric: metric.value });
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString();
}
</script>
