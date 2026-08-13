<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.saving"
        @click="onRestart"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.saving }" />
        Restart workers
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="onSample"
      >
        <PaperAirplaneIcon class="h-4 w-4" />
        Dispatch sample
      </button>
    </Teleport>

    <QueueSubnav />

    <div v-if="store.loading && !dash" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!dash"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load queue dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading queue metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="store.fetchDashboard()"
      >
        Retry
      </button>
    </div>

    <template v-else>
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight capitalize text-slate-900">
              {{ card.value }}
            </p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <section class="mb-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Queue sizes</h2>
            <p class="mt-0.5 text-xs text-slate-500">Waiting jobs by named queue</p>
          </div>
          <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            {{ pendingTotal }} pending
          </span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="queue in queueEntries"
            :key="queue.name"
            class="rounded-[12px] px-4 py-3 ring-1"
            :class="
              queue.size > 0
                ? 'bg-amber-50/70 ring-amber-100'
                : 'bg-zinc-50/80 ring-zinc-100'
            "
          >
            <div class="mb-2 flex items-center justify-between gap-2">
              <p class="truncate font-medium capitalize text-slate-900">{{ queue.name }}</p>
              <span
                class="shrink-0 text-xs font-medium"
                :class="queue.size > 0 ? 'text-amber-700' : 'text-slate-500'"
              >
                {{ queue.size }} waiting
              </span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-white/80">
              <div
                class="h-full rounded-full transition-all"
                :class="queue.size > 0 ? 'bg-amber-500' : 'bg-zinc-200'"
                :style="{ width: `${queue.percent}%` }"
              />
            </div>
          </div>
        </div>

        <div class="mt-5 flex flex-col gap-2 rounded-[12px] bg-zinc-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Worker suggestion</p>
            <code class="mt-1 block truncate text-xs text-slate-700">{{ workerCommand }}</code>
          </div>
          <button
            type="button"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-[12px] border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50"
            @click="copyWorkerCommand"
          >
            <CheckIcon v-if="copied" class="h-4 w-4 text-emerald-600" />
            <ClipboardDocumentIcon v-else class="h-4 w-4 text-slate-400" />
            {{ copied ? 'Copied' : 'Copy' }}
          </button>
        </div>
      </section>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Recent tracks</h2>
            <RouterLink
              :to="{ name: 'queue.running' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Running jobs
            </RouterLink>
          </div>
          <div v-if="!(dash.recent_tracks || []).length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No tracked jobs yet</p>
            <p class="mt-1 text-xs text-slate-500">Dispatched jobs will appear here as they are tracked.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in dash.recent_tracks"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">
                  {{ item.display_name || item.job_class }}
                </p>
                <p class="mt-1 text-xs capitalize text-slate-500">
                  {{ item.type }} · {{ item.queue }}
                </p>
              </div>
              <div class="flex shrink-0 flex-col items-end gap-1">
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                  :class="statusClass(item.status)"
                >
                  {{ item.status }}
                </span>
                <span class="text-xs text-slate-400">{{ formatDate(item.created_at) }}</span>
              </div>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Recent failures</h2>
            <RouterLink
              :to="{ name: 'queue.failed' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Manage failed
            </RouterLink>
          </div>
          <div v-if="!(dash.recent_failed || []).length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No failed jobs</p>
            <p class="mt-1 text-xs text-slate-500">Failed queue jobs will show here for retry.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in dash.recent_failed"
              :key="item.uuid"
              class="py-3.5 first:pt-0 last:pb-0"
            >
              <div class="mb-1 flex items-start justify-between gap-3">
                <p class="truncate text-sm font-medium text-slate-900">{{ item.display_name }}</p>
                <span class="shrink-0 text-xs text-slate-400">{{ formatDate(item.failed_at) }}</span>
              </div>
              <p class="line-clamp-2 text-xs text-rose-600">{{ item.exception }}</p>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  BoltIcon,
  CheckCircleIcon,
  CheckIcon,
  CircleStackIcon,
  ClipboardDocumentIcon,
  ClockIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  PaperAirplaneIcon,
  PlayCircleIcon,
  ShieldCheckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const toast = useToast();
const dash = computed(() => store.dashboard);
const copied = ref(false);
let copyTimer;

const cards = computed(() => {
  const d = dash.value || {};
  const pending = d.pending?.pending ?? 0;
  const running = d.tracks?.running ?? d.pending?.running ?? 0;
  const failed = d.failed_count ?? 0;

  return [
    {
      label: 'Connection',
      value: d.connection || '—',
      hint: 'Queue driver',
      icon: CircleStackIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-500',
    },
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'Jobs waiting for workers' : 'Queue is idle',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Running',
      value: running,
      hint: 'Currently executing',
      icon: PlayCircleIcon,
      iconBg: running ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: running ? 'text-brand-500' : 'text-slate-500',
    },
    {
      label: 'Failed',
      value: failed,
      hint: failed ? 'Needs retry or review' : 'No failures',
      icon: ExclamationCircleIcon,
      iconBg: failed ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: failed ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Tracked completed',
      value: d.tracks?.completed ?? 0,
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
    },
    {
      label: 'Tracked failed',
      value: d.tracks?.failed ?? 0,
      icon: XCircleIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-500',
    },
    {
      label: 'Imports',
      value: d.by_type?.import ?? 0,
      icon: ArrowDownTrayIcon,
      iconBg: 'bg-violet-50',
      iconColor: 'text-violet-500',
    },
    {
      label: 'Webhooks',
      value: d.by_type?.webhook ?? 0,
      icon: BoltIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
  ];
});

const queueEntries = computed(() => {
  const sizes = dash.value?.queue_sizes || {};
  const values = Object.values(sizes).map((size) => Number(size) || 0);
  const max = Math.max(1, ...values);

  return Object.entries(sizes).map(([name, size]) => {
    const waiting = Number(size) || 0;
    return {
      name,
      size: waiting,
      percent: waiting ? Math.max(8, Math.round((waiting / max) * 100)) : 0,
    };
  });
});

const pendingTotal = computed(() => dash.value?.pending?.pending ?? 0);

const busiestQueue = computed(() => {
  if (!queueEntries.value.length) return null;
  return [...queueEntries.value].sort((a, b) => b.size - a.size)[0];
});

const workerCommand = computed(
  () => `php artisan queue:work --queue=${(dash.value?.worker_queues || []).join(',')}`,
);

const healthMessage = computed(() => {
  const failed = dash.value?.failed_count ?? 0;
  const pending = pendingTotal.value;
  const busy = busiestQueue.value;

  if (failed > 0) {
    return `${failed} failed job${failed === 1 ? '' : 's'} need attention. Retry or remove them from Failed.`;
  }
  if (pending > 0 && busy?.size > 0) {
    return `${pending} job${pending === 1 ? '' : 's'} waiting. Busiest queue: ${busy.name} (${busy.size}).`;
  }
  return 'Queue is healthy. No failed jobs and workers can drain the backlog.';
});

const healthTone = computed(() => {
  if ((dash.value?.failed_count ?? 0) > 0) {
    return 'bg-rose-50 text-rose-800';
  }
  if (pendingTotal.value > 0) {
    return 'bg-amber-50 text-amber-800';
  }
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  if ((dash.value?.failed_count ?? 0) > 0) return ExclamationTriangleIcon;
  if (pendingTotal.value > 0) return ClockIcon;
  return ShieldCheckIcon;
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

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  store.fetchDashboard();
});

async function onRestart() {
  if (!window.confirm('Signal all queue workers to restart after they finish the current job?')) {
    return;
  }
  await store.restartWorkers();
  await store.fetchDashboard();
}

async function onSample() {
  await store.dispatchSample({ channel: 'in_app', priority: 'normal', delay_seconds: 0 });
  await store.fetchDashboard();
}

async function copyWorkerCommand() {
  try {
    await navigator.clipboard.writeText(workerCommand.value);
    copied.value = true;
    toast.success('Worker command copied');
    window.clearTimeout(copyTimer);
    copyTimer = window.setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch {
    toast.error('Unable to copy command');
  }
}

function statusClass(status) {
  if (status === 'completed') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running') return 'bg-amber-50 text-amber-700';
  if (status === 'queued') return 'bg-sky-50 text-sky-700';
  return 'bg-zinc-100 text-slate-600';
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
