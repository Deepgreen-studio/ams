<template>
  <div>
    <PageHeader title="Real-Time Status" description="Live health board across services, APIs, queues, and checks.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="store.saving"
          @click="onCapture"
        >
          Capture now
        </button>
        <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">
          Refresh
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

    <div v-if="store.loading && !board" class="grid gap-4 md:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <template v-else-if="board">
      <p v-if="board.generated_at" class="mb-4 text-xs text-slate-500">
        Generated {{ board.generated_at }}
      </p>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in scoreCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="status in statusCards"
          :key="status.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ status.label }}</p>
          <p class="mt-2 text-sm font-semibold capitalize" :class="statusClass(status.value)">
            {{ status.value }}
          </p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">API</h3>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Requests</dt>
              <dd>{{ board.api?.total ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Failed</dt>
              <dd>{{ board.api?.failed ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Avg ms</dt>
              <dd>{{ board.api?.avg_response_ms ?? 0 }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Webhooks</h3>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Total</dt>
              <dd>{{ board.webhooks?.total ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Success rate</dt>
              <dd>{{ board.webhooks?.success_rate ?? 0 }}%</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Failed</dt>
              <dd>{{ board.webhooks?.failed ?? 0 }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Queue</h3>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Pending</dt>
              <dd>{{ board.queue?.pending ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Failed</dt>
              <dd>{{ board.queue?.failed ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Health</dt>
              <dd>{{ board.queue?.health_score ?? 0 }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="service in services"
          :key="service.uuid"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-medium text-slate-900">{{ service.name }}</p>
              <p class="text-xs capitalize text-slate-500">{{ service.service_type }}</p>
            </div>
            <span
              class="rounded-md px-2 py-1 text-xs font-medium capitalize"
              :class="badgeClass(service.status)"
            >
              {{ service.status }}
            </span>
          </div>
          <p class="mt-3 text-xs text-slate-500">Last check {{ service.last_check_at || '—' }}</p>
          <p v-if="service.avg_response_ms != null" class="mt-1 text-xs text-slate-500">
            {{ service.avg_response_ms }} ms
          </p>
        </div>
        <div
          v-if="!services.length"
          class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500 sm:col-span-2 xl:col-span-3"
        >
          No service status rows yet. Click <span class="font-medium text-slate-700">Capture now</span> to probe
          services and populate this board.
        </div>
      </div>

      <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-sm font-semibold text-slate-900">Latest health checks</h3>
        <div v-if="checks.length" class="mt-3 grid gap-2 sm:grid-cols-2">
          <div
            v-for="check in checks"
            :key="check.uuid"
            class="rounded-lg border border-slate-100 px-3 py-2 text-sm"
          >
            <div class="flex items-center justify-between gap-2">
              <p class="font-medium text-slate-800">{{ check.name }}</p>
              <span class="text-xs capitalize" :class="statusClass(check.status)">{{ check.status }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ check.message || '—' }}</p>
          </div>
        </div>
        <p v-else class="mt-3 text-sm text-slate-500">
          No health checks recorded yet. Capture a snapshot to run probes.
        </p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const board = computed(() => store.realtime);
const services = computed(() => board.value?.services || []);
const checks = computed(() => board.value?.health_checks || []);
let timer = null;

const scoreCards = computed(() => [
  { label: 'Health', value: board.value?.scores?.health_score ?? '—' },
  { label: 'Performance', value: board.value?.scores?.performance_score ?? '—' },
  { label: 'Error rate', value: `${board.value?.scores?.error_rate ?? 0}%` },
  { label: 'Queue health', value: board.value?.scores?.queue_health_score ?? '—' },
]);

const statusCards = computed(() => {
  const s = board.value?.statuses || {};
  return [
    { label: 'Availability', value: s.availability || 'unknown' },
    { label: 'Authentication', value: s.authentication || 'unknown' },
    { label: 'Rate limits', value: s.rate_limits || 'unknown' },
    { label: 'Server', value: s.server || 'unknown' },
    { label: 'Queue', value: s.queue || 'unknown' },
  ];
});

function statusClass(value) {
  if (value === 'healthy') return 'text-emerald-700';
  if (value === 'degraded') return 'text-amber-700';
  if (value === 'unhealthy') return 'text-rose-700';
  return 'text-slate-600';
}

function badgeClass(value) {
  if (value === 'healthy') return 'bg-emerald-50 text-emerald-700';
  if (value === 'degraded') return 'bg-amber-50 text-amber-700';
  if (value === 'unhealthy') return 'bg-rose-50 text-rose-700';
  return 'bg-slate-100 text-slate-700';
}

async function load() {
  try {
    await store.fetchRealtime();
  } catch {
    // Error is stored in the monitoring store for display.
  }
}

async function onCapture() {
  try {
    await store.capture();
    await load();
  } catch {
    // Error is stored in the monitoring store for display.
  }
}

onMounted(async () => {
  await load();
  timer = setInterval(load, 30000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>
