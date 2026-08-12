<template>
  <div>
    <SchedulerSubnav />

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 10" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
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

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import {
  CheckCircleIcon,
  ClockIcon,
  CubeIcon,
  ExclamationCircleIcon,
  PlayCircleIcon,
  QueueListIcon,
  RocketLaunchIcon,
  SparklesIcon,
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
    label: 'One-time',
    value: store.statistics?.one_time ?? 0,
    icon: SparklesIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-500',
  },
  {
    label: 'Queue jobs',
    value: store.statistics?.queue ?? 0,
    icon: CubeIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
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
    label: 'Queued',
    value: store.runStatistics?.queued ?? 0,
    icon: ClockIcon,
    iconBg: 'bg-zinc-100',
    iconColor: 'text-slate-500',
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
  {
    label: 'Pending',
    value: store.runStatistics?.pending ?? 0,
    icon: ClockIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
]);

onMounted(() => store.fetchStatistics());
</script>
