<template>
  <div>
    <PageHeader
      title="Health Dashboard"
      description="Integration Hub health, performance, uptime, and operational monitors."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="onCapture"
        >
          Capture snapshot
        </button>
      </template>
    </PageHeader>
    <MonitoringSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !dash" class="grid gap-4 md:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-24 animate-pulse rounded-xl bg-slate-100" />
    </div>
    <template v-else-if="dash">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="card in scoreCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="status in statusCards"
          :key="status.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            {{ status.label }}
          </p>
          <p class="mt-2 text-sm font-semibold capitalize" :class="statusClass(status.value)">
            {{ status.value }}
          </p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Response time"
          subtitle="Last 24h avg ms"
          :points="responsePoints"
          value-key="avg_response_ms"
        />
        <SimpleLineChart
          title="Health trend"
          subtitle="Captured snapshots"
          :points="healthPoints"
          value-key="health_score"
          stroke="#059669"
          fill="#059669"
        />
      </div>

      <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">API</h2>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Requests</dt>
              <dd>{{ dash.api?.total ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Failed</dt>
              <dd>{{ dash.api?.failed ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Avg ms</dt>
              <dd>{{ dash.api?.avg_response_ms ?? 0 }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Webhooks
          </h2>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Total</dt>
              <dd>{{ dash.webhooks?.total ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Success rate</dt>
              <dd>{{ dash.webhooks?.success_rate ?? 0 }}%</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Failed</dt>
              <dd>{{ dash.webhooks?.failed ?? 0 }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Queue</h2>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Pending</dt>
              <dd>{{ dash.queue?.pending ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Failed</dt>
              <dd>{{ dash.queue?.failed ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Health</dt>
              <dd>{{ dash.queue?.health_score ?? 0 }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import SimpleLineChart from '@/modules/monitoring/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const dash = computed(() => store.dashboard);

const scoreCards = computed(() => {
  const s = dash.value?.scores || {};
  return [
    { label: 'Health score', value: s.health_score ?? 0 },
    { label: 'Performance', value: s.performance_score ?? 0 },
    { label: 'Uptime', value: `${s.uptime_percent ?? 0}%` },
    { label: 'Downtime', value: `${s.downtime_percent ?? 0}%` },
    { label: 'Error rate', value: `${s.error_rate ?? 0}%` },
  ];
});

const statusCards = computed(() => {
  const s = dash.value?.statuses || {};
  return [
    { label: 'Availability', value: s.availability || 'unknown' },
    { label: 'Authentication', value: s.authentication || 'unknown' },
    { label: 'Rate limits', value: s.rate_limits || 'unknown' },
    { label: 'Server/integrations', value: s.server || 'unknown' },
    { label: 'Queue', value: s.queue || 'unknown' },
  ];
});

const responsePoints = computed(() => dash.value?.charts?.response_history || []);
const healthPoints = computed(() => dash.value?.charts?.health_trend || []);

onMounted(() => store.fetchDashboard());

async function onCapture() {
  await store.capture();
  await store.fetchDashboard();
}

function statusClass(value) {
  if (value === 'healthy') return 'text-emerald-700';
  if (value === 'degraded') return 'text-amber-700';
  if (value === 'unhealthy') return 'text-rose-700';
  return 'text-slate-700';
}
</script>
