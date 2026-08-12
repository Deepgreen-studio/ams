<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'scheduler.failed' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Failed jobs
      </RouterLink>
      <RouterLink
        :to="{ name: 'scheduler.jobs.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        New job
      </RouterLink>
    </Teleport>

    <SchedulerSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <template v-else>
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in jobCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in runCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>
    </template>

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Recent runs</h2>
          <RouterLink
            :to="{ name: 'scheduler.history' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View history
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recentRuns.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <p v-else-if="!store.recentRuns.length" class="py-10 text-center text-sm text-slate-500">
          No runs yet.
        </p>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="run in store.recentRuns"
            :key="run.uuid"
            class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-slate-900">{{ run.job?.name || 'Job' }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ formatDate(run.created_at) }}</p>
            </div>
            <span
              class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
              :class="statusClass(run.status)"
            >
              {{ run.status }}
            </span>
          </li>
        </ul>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Recent failures</h2>
          <RouterLink
            :to="{ name: 'scheduler.failed' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View failed
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recentFailed.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <p v-else-if="!store.recentFailed.length" class="py-10 text-center text-sm text-slate-500">
          No failures.
        </p>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="run in store.recentFailed"
            :key="run.uuid"
            class="py-3.5 first:pt-0 last:pb-0"
          >
            <p class="text-sm font-medium text-slate-900">{{ run.job?.name || 'Job' }}</p>
            <p class="mt-0.5 text-xs text-rose-600">{{ run.error_message || 'Failed' }}</p>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowPathIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  PlayCircleIcon,
  QueueListIcon,
  RocketLaunchIcon,
} from '@heroicons/vue/24/outline';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();

const hasStats = computed(() => store.statistics != null || store.runStatistics != null);

const jobCards = computed(() => [
  {
    label: 'Total jobs',
    value: store.statistics?.total ?? 0,
    icon: QueueListIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Enabled',
    value: store.statistics?.enabled ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Cron',
    value: store.statistics?.cron ?? 0,
    icon: CalendarDaysIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
  {
    label: 'Recurring',
    value: store.statistics?.recurring ?? 0,
    icon: ArrowPathIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-500',
  },
]);

const runCards = computed(() => [
  {
    label: 'Total runs',
    value: store.runStatistics?.total ?? 0,
    icon: RocketLaunchIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Running',
    value: store.runStatistics?.running ?? 0,
    icon: PlayCircleIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
  {
    label: 'Success',
    value: store.runStatistics?.success ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Failed',
    value: store.runStatistics?.failed ?? 0,
    icon: ExclamationCircleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-500',
  },
]);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusClass(status) {
  if (status === 'success') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running') return 'bg-amber-50 text-amber-700';
  return 'bg-zinc-100 text-slate-600';
}

onMounted(() => store.fetchDashboard());
</script>
