<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="store.saving"
        @click="onCapture"
      >
        {{ store.saving ? 'Capturing…' : 'Capture snapshot' }}
      </button>
    </Teleport>

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

    <div v-if="store.loading && !dash" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <template v-else-if="dash">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="card in scoreCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ card.label }}
            </p>
            <p
              class="mt-1 text-2xl font-bold tracking-tight"
              :class="card.tone || 'text-slate-900'"
            >
              {{ card.value }}
            </p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="status in statusCards"
          :key="status.label"
          class="rounded-[12px] bg-white px-5 py-4 ring-1 ring-zinc-100"
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

      <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="mb-4 text-base font-semibold text-slate-900">API</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Requests</dt>
              <dd class="font-medium text-slate-900">{{ dash.api?.total ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Failed</dt>
              <dd class="font-medium text-slate-900">{{ dash.api?.failed ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Avg ms</dt>
              <dd class="font-medium text-slate-900">{{ dash.api?.avg_response_ms ?? 0 }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="mb-4 text-base font-semibold text-slate-900">Webhooks</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Total</dt>
              <dd class="font-medium text-slate-900">{{ dash.webhooks?.total ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Success rate</dt>
              <dd class="font-medium text-slate-900">{{ dash.webhooks?.success_rate ?? 0 }}%</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Failed</dt>
              <dd class="font-medium text-slate-900">{{ dash.webhooks?.failed ?? 0 }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="mb-4 text-base font-semibold text-slate-900">Queue</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Pending</dt>
              <dd class="font-medium text-slate-900">{{ dash.queue?.pending ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Failed</dt>
              <dd class="font-medium text-slate-900">{{ dash.queue?.failed ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Health</dt>
              <dd class="font-medium text-slate-900">{{ dash.queue?.health_score ?? 0 }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import {
  HeartIcon,
  BoltIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import SimpleLineChart from '@/modules/monitoring/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const toast = useToast();
const dash = computed(() => store.dashboard);

const scoreCards = computed(() => {
  const s = dash.value?.scores || {};
  return [
    {
      label: 'Health score',
      value: s.health_score ?? 0,
      icon: HeartIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Performance',
      value: s.performance_score ?? 0,
      icon: BoltIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-500',
    },
    {
      label: 'Uptime',
      value: `${s.uptime_percent ?? 0}%`,
      icon: ArrowTrendingUpIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
      tone: (s.uptime_percent ?? 0) < 50 ? 'text-rose-600' : 'text-slate-900',
    },
    {
      label: 'Downtime',
      value: `${s.downtime_percent ?? 0}%`,
      icon: ArrowTrendingDownIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-500',
      tone: (s.downtime_percent ?? 0) > 50 ? 'text-rose-600' : 'text-slate-900',
    },
    {
      label: 'Error rate',
      value: `${s.error_rate ?? 0}%`,
      icon: ExclamationTriangleIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-500',
      tone: (s.error_rate ?? 0) > 50 ? 'text-rose-600' : 'text-slate-900',
    },
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
  if (store.saving) return;
  try {
    await store.capture();
    toast.success(store.successMessage || 'Health snapshot captured.');
    await store.fetchDashboard({}, { preserveMessages: true });
  } catch (err) {
    toast.error(err?.message || store.error || 'Unable to capture snapshot');
  }
}

function statusClass(value) {
  if (value === 'healthy') return 'text-emerald-700';
  if (value === 'degraded') return 'text-amber-700';
  if (value === 'unhealthy') return 'text-rose-700';
  return 'text-slate-700';
}
</script>
