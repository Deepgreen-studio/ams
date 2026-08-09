<template>
  <div class="space-y-5">
    <div
      v-if="dashboardStore.loading && !dashboardStore.data"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <div
        v-for="n in 4"
        :key="n"
        class="h-36 animate-pulse rounded-2xl bg-white ring-1 ring-zinc-100"
      />
    </div>

    <div
      v-else-if="dashboardStore.error && !dashboardStore.data"
      class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-6 text-sm text-rose-700"
    >
      {{ dashboardStore.error }}
      <button type="button" class="ml-2 font-medium underline" @click="reload">Retry</button>
    </div>

    <template v-else>
      <!-- Overview metrics -->
      <section>
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-zinc-800">Overview</h2>
          <select
            v-model="selectedDays"
            class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 outline-none hover:bg-zinc-50 focus:border-brand-500"
            @change="onDaysChange"
          >
            <option :value="7">Last 7 days</option>
            <option :value="30">Last 30 days</option>
            <option :value="90">Last 90 days</option>
          </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <MetricCard
            v-for="metric in metricCards"
            :key="metric.key"
            v-bind="metric"
          />
        </div>
      </section>

      <!-- Summary + progress -->
      <section class="grid gap-5 xl:grid-cols-3">
        <div class="xl:col-span-2">
          <ApplicationSummaryCard :rows="dashboardStore.applicationSummary" />
        </div>
        <OverallProgressCard :progress="dashboardStore.overallProgress" />
      </section>

      <!-- Tasks + workload -->
      <section class="grid gap-5 xl:grid-cols-2">
        <TodayTasksCard :payload="dashboardStore.todaysTasks" />
        <TeamWorkloadCard :payload="dashboardStore.teamWorkload" />
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
  FolderIcon,
  TicketIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import MetricCard from '@/components/dashboard/MetricCard.vue';
import ApplicationSummaryCard from '@/components/dashboard/ApplicationSummaryCard.vue';
import OverallProgressCard from '@/components/dashboard/OverallProgressCard.vue';
import TodayTasksCard from '@/components/dashboard/TodayTasksCard.vue';
import TeamWorkloadCard from '@/components/dashboard/TeamWorkloadCard.vue';
import { useDashboardStore } from '@/modules/dashboard/stores/dashboard';

const dashboardStore = useDashboardStore();
const selectedDays = ref(dashboardStore.days || 30);

const iconMap = {
  applications: {
    icon: FolderIcon,
    iconBg: 'bg-orange-50',
    iconColor: 'text-brand-500',
  },
  customers: {
    icon: UsersIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-600',
  },
  open_tickets: {
    icon: TicketIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-600',
  },
  users: {
    icon: UserGroupIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
};

const metricCards = computed(() =>
  dashboardStore.metrics.map((metric) => {
    const icons = iconMap[metric.key] || iconMap.applications;
    return {
      key: metric.key,
      label: metric.label,
      value: String(metric.value ?? '0'),
      hint: metric.secondary || metric.hint || '',
      secondary: metric.secondary || '',
      trendLabel: metric.trend_label || '0% change',
      trendUp: Boolean(metric.trend_up),
      trendFavorable: metric.trend_favorable,
      ...icons,
    };
  }),
);

onMounted(() => {
  dashboardStore.fetchOverview({ days: selectedDays.value }).catch(() => {});
});

function onDaysChange() {
  dashboardStore.fetchOverview({ days: Number(selectedDays.value) }).catch(() => {});
}

function reload() {
  dashboardStore.fetchOverview({ days: Number(selectedDays.value) }).catch(() => {});
}
</script>
