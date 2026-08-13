<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'sync.configs.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New sync config
      </RouterLink>
    </Teleport>

    <SyncSubnav />

    <div v-if="store.loading && !totals" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 10" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !totals"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load sync dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading sync metrics again.</p>
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
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
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

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Recent runs</h2>
            <RouterLink
              :to="{ name: 'sync.history' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View history
            </RouterLink>
          </div>
          <div v-if="!recentRuns.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No sync runs yet</p>
            <p class="mt-1 text-xs text-slate-500">
              Runs appear here after a config is executed.
            </p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li v-for="run in recentRuns" :key="run.uuid" class="py-3.5 first:pt-0 last:pb-0">
              <div class="mb-2 flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-slate-900">
                    {{ run.config?.name || 'Sync run' }}
                  </p>
                  <p class="mt-1 text-xs capitalize text-slate-500">
                    {{ run.trigger }} · {{ run.mode }}
                  </p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                  <span
                    class="rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                    :class="statusClass(run.status)"
                  >
                    {{ run.status }}
                  </span>
                  <span class="text-xs text-slate-400">
                    {{ formatDate(run.started_at || run.created_at) }}
                  </span>
                </div>
              </div>
              <SyncProgressBar :percent="run.progress_percent" :status="run.status" />
              <p class="mt-2 text-xs text-slate-500">
                {{ run.imported }} imported · {{ run.updated }} updated · {{ run.failed }} failed ·
                {{ run.skipped }} skipped
              </p>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Configs</h2>
            <RouterLink
              :to="{ name: 'sync.configs' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Manage
            </RouterLink>
          </div>
          <div v-if="!configs.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No sync configurations</p>
            <p class="mt-1 text-xs text-slate-500">
              Create a config to import or export data for an integration.
            </p>
            <RouterLink
              :to="{ name: 'sync.configs.create' }"
              class="mt-4 inline-flex rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            >
              New sync config
            </RouterLink>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in configs"
              :key="item.uuid"
              class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ item.name }}</p>
                <p class="mt-1 text-xs capitalize text-slate-500">
                  {{ item.direction }} · {{ item.trigger_type }}
                </p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="
                    item.is_enabled
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-zinc-100 text-slate-500'
                  "
                >
                  {{ item.is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
                <RouterLink
                  :to="{ name: 'sync.configs.show', params: { id: item.uuid } }"
                  class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  Open
                </RouterLink>
              </div>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ArrowUpTrayIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  ForwardIcon,
  PencilSquareIcon,
  PlayCircleIcon,
  PlusIcon,
  QueueListIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SyncProgressBar from '@/modules/sync/components/SyncProgressBar.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const store = useSyncStore();
const toast = useToast();

const totals = computed(() => store.dashboard?.totals ?? null);
const recentRuns = computed(() => store.dashboard?.recent_runs ?? []);
const configs = computed(() => store.dashboard?.configs?.items ?? []);

const cards = computed(() => {
  const t = totals.value || {};
  const pending = t.pending ?? 0;
  const queued = t.queued ?? 0;
  const running = t.running ?? 0;
  const failed = t.failed ?? 0;

  return [
    {
      label: 'Total runs',
      value: t.total_runs ?? 0,
      hint: 'All sync executions',
      icon: ArrowPathIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'Waiting to start' : 'Nothing waiting',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Queued',
      value: queued,
      hint: 'In the background queue',
      icon: QueueListIcon,
      iconBg: queued ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: queued ? 'text-sky-500' : 'text-slate-500',
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
      label: 'Completed',
      value: t.completed ?? 0,
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
    },
    {
      label: 'Failed',
      value: failed,
      hint: failed ? 'Needs review' : 'No failures',
      icon: ExclamationCircleIcon,
      iconBg: failed ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: failed ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Imported',
      value: t.imported ?? 0,
      icon: ArrowDownTrayIcon,
      iconBg: 'bg-violet-50',
      iconColor: 'text-violet-500',
    },
    {
      label: 'Updated',
      value: t.updated ?? 0,
      icon: PencilSquareIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-500',
    },
    {
      label: 'Skipped',
      value: t.skipped ?? 0,
      icon: ForwardIcon,
      iconBg: 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
    {
      label: 'Exported',
      value: t.exported ?? 0,
      icon: ArrowUpTrayIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-500',
    },
  ];
});

const healthMessage = computed(() => {
  const t = totals.value || {};
  const failed = t.failed ?? 0;
  const running = t.running ?? 0;
  const pending = (t.pending ?? 0) + (t.queued ?? 0);

  if (!configs.value.length) {
    return 'No sync configurations yet. Create a config to start importing or exporting data.';
  }
  if (failed > 0) {
    return `${failed} failed run${failed === 1 ? '' : 's'} need review in History.`;
  }
  if (running > 0) {
    return `${running} sync run${running === 1 ? ' is' : 's are'} currently executing.`;
  }
  if (pending > 0) {
    return `${pending} run${pending === 1 ? '' : 's'} waiting to start.`;
  }
  return 'Sync is healthy. No failed or in-progress runs.';
});

const healthTone = computed(() => {
  const t = totals.value || {};
  if ((t.failed ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if (!configs.value.length) return 'bg-zinc-100 text-slate-700';
  if ((t.running ?? 0) > 0 || (t.pending ?? 0) > 0 || (t.queued ?? 0) > 0) {
    return 'bg-amber-50 text-amber-800';
  }
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const t = totals.value || {};
  if ((t.failed ?? 0) > 0) return ExclamationTriangleIcon;
  if (!configs.value.length) return QueueListIcon;
  if ((t.running ?? 0) > 0 || (t.pending ?? 0) > 0 || (t.queued ?? 0) > 0) return ClockIcon;
  return ShieldCheckIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !totals.value) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.error = null;
  store.fetchDashboard();
});

function statusClass(status) {
  if (status === 'completed') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running') return 'bg-amber-50 text-amber-700';
  if (status === 'queued') return 'bg-sky-50 text-sky-700';
  if (status === 'cancelled') return 'bg-zinc-100 text-slate-600';
  return 'bg-zinc-100 text-slate-600';
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
