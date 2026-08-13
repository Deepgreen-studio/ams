<template>
  <div>
    <QueueSubnav />

    <div v-if="store.loading && !stats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <template v-else-if="stats">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight capitalize text-slate-900">
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

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="mb-4 text-base font-semibold text-slate-900">By status</h2>
          <div v-if="!statusRows.length" class="py-10 text-center text-sm text-slate-500">
            No tracked jobs yet.
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="row in statusRows"
              :key="row.key"
              class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full" :class="statusDot(row.key)" />
                <span class="text-sm capitalize text-slate-600">{{ row.key }}</span>
              </div>
              <span class="text-sm font-semibold text-slate-900">{{ row.value }}</span>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="mb-4 text-base font-semibold text-slate-900">By type</h2>
          <div v-if="!typeRows.length" class="py-10 text-center text-sm text-slate-500">
            No tracked jobs yet.
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="row in typeRows"
              :key="row.key"
              class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <span class="text-sm capitalize text-slate-600">{{ row.key }}</span>
              <span class="text-sm font-semibold text-slate-900">{{ row.value }}</span>
            </li>
          </ul>
        </section>
      </div>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4">
          <h2 class="text-base font-semibold text-slate-900">Queue depth</h2>
          <p class="mt-0.5 text-xs text-slate-500">Waiting jobs across priority and functional queues</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="queue in queueEntries"
            :key="queue.name"
            class="rounded-[12px] px-4 py-3 ring-1"
            :class="queue.size > 0 ? 'bg-amber-50/70 ring-amber-100' : 'bg-zinc-50/80 ring-zinc-100'"
          >
            <div class="mb-2 flex items-center justify-between gap-2">
              <p class="truncate font-medium capitalize text-slate-900">{{ queue.name }}</p>
              <span
                class="shrink-0 text-xs font-medium"
                :class="queue.size > 0 ? 'text-amber-700' : 'text-slate-500'"
              >
                {{ queue.size }}
              </span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-white/80">
              <div
                class="h-full rounded-full"
                :class="queue.size > 0 ? 'bg-amber-500' : 'bg-zinc-200'"
                :style="{ width: `${queue.percent}%` }"
              />
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import {
  CheckCircleIcon,
  CircleStackIcon,
  ExclamationCircleIcon,
  TableCellsIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const toast = useToast();
const stats = computed(() => store.statistics);

const summaryCards = computed(() => [
  {
    label: 'Connection',
    value: stats.value?.connection || '—',
    icon: CircleStackIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
  {
    label: 'Jobs table',
    value: stats.value?.database_jobs_table ?? 0,
    icon: TableCellsIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-500',
  },
  {
    label: 'Failed jobs',
    value: stats.value?.failed_jobs ?? 0,
    icon: ExclamationCircleIcon,
    iconBg: (stats.value?.failed_jobs ?? 0) > 0 ? 'bg-rose-50' : 'bg-emerald-50',
    iconColor: (stats.value?.failed_jobs ?? 0) > 0 ? 'text-rose-500' : 'text-emerald-500',
  },
  {
    label: 'Completed (24h)',
    value: stats.value?.jobs_last_24h?.completed ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
]);

const statusRows = computed(() =>
  Object.entries(stats.value?.track_status || {}).map(([key, value]) => ({ key, value })),
);

const typeRows = computed(() =>
  Object.entries(stats.value?.track_types || {}).map(([key, value]) => ({ key, value })),
);

const queueEntries = computed(() => {
  const sizes = stats.value?.queue_sizes || {};
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

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

function statusDot(status) {
  if (status === 'completed') return 'bg-emerald-500';
  if (status === 'failed') return 'bg-rose-500';
  if (status === 'running') return 'bg-amber-500';
  if (status === 'queued') return 'bg-sky-500';
  return 'bg-zinc-400';
}

onMounted(() => {
  store.error = null;
  store.fetchStatistics();
});
</script>
