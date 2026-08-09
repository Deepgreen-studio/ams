<template>
  <div class="space-y-5">
    <!-- Overview metrics -->
    <section>
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-zinc-800">Overview</h2>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
        >
          Last 30 days
          <ChevronDownIcon class="h-3.5 w-3.5" />
        </button>
      </div>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          v-for="metric in metrics"
          :key="metric.label"
          v-bind="metric"
        />
      </div>
    </section>

    <!-- Summary + progress -->
    <section class="grid gap-5 xl:grid-cols-3">
      <div class="xl:col-span-2">
        <ApplicationSummaryCard />
      </div>
      <OverallProgressCard />
    </section>

    <!-- Tasks + workload -->
    <section class="grid gap-5 xl:grid-cols-2">
      <TodayTasksCard />
      <TeamWorkloadCard />
    </section>
  </div>
</template>

<script setup>
import {
  ChevronDownIcon,
  ClockIcon,
  CurrencyDollarIcon,
  FolderIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import MetricCard from '@/components/dashboard/MetricCard.vue';
import ApplicationSummaryCard from '@/components/dashboard/ApplicationSummaryCard.vue';
import OverallProgressCard from '@/components/dashboard/OverallProgressCard.vue';
import TodayTasksCard from '@/components/dashboard/TodayTasksCard.vue';
import TeamWorkloadCard from '@/components/dashboard/TeamWorkloadCard.vue';

const metrics = [
  {
    label: 'Total revenue',
    value: '$53,009.89',
    hint: 'from last month',
    trendLabel: '12% increase',
    trendUp: true,
    icon: CurrencyDollarIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-600',
  },
  {
    label: 'Applications',
    value: '95/100',
    hint: 'from last month',
    trendLabel: '10% decrease',
    trendUp: false,
    icon: FolderIcon,
    iconBg: 'bg-orange-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Time spent',
    value: '1022/1300 Hrs',
    hint: 'from last month',
    trendLabel: '8% increase',
    trendUp: true,
    icon: ClockIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-600',
  },
  {
    label: 'Resources',
    value: '101/120',
    hint: 'from last month',
    trendLabel: '2% increase',
    trendUp: true,
    icon: UserGroupIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
];
</script>
