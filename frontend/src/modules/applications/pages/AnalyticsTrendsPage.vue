<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-for="link in navLinks"
          :key="link.name"
          :to="{ name: link.name, params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] px-5 py-2.5 text-sm font-medium transition"
          :class="
            isActive(link.name)
              ? 'bg-brand-600 text-white hover:bg-brand-700'
              : 'border border-zinc-200 text-slate-700 hover:bg-zinc-50'
          "
        >
          {{ link.label }}
        </RouterLink>
        <SelectBox
          v-model="metric"
          size="lg"
          wrapper-class="min-w-[12rem]"
          :options="metricOptions"
          @change="reload"
        />
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.loading && !analyticsStore.trends"
      class="mb-4 grid gap-4 sm:grid-cols-3"
    >
      <div v-for="n in 3" :key="n" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else-if="analyticsStore.trends" class="mb-4 grid gap-4 sm:grid-cols-3">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight" :class="card.valueClass">
            {{ card.value }}
          </p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <SimpleLineChart
      :title="`Trend: ${metricLabel}`"
      hint="Compared to previous period"
      :labels="analyticsStore.trends?.labels || []"
      :series="[{ key: 'metric', label: metricLabel, values: analyticsStore.trends?.values || [] }]"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowTrendingDownIcon,
  ArrowTrendingUpIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const analyticsStore = useAnalyticsStore();
const toast = useToast();
const metric = ref('daily_users');

const navLinks = [
  { name: 'applications.analytics', label: 'Dashboard' },
  { name: 'applications.analytics.trends', label: 'Trends' },
  { name: 'applications.analytics.heatmap', label: 'Heatmap' },
  { name: 'applications.analytics.countries', label: 'Countries' },
  { name: 'applications.analytics.devices', label: 'Devices' },
];

const metricOptions = [
  { value: 'daily_users', label: 'Daily users' },
  { value: 'monthly_users', label: 'Monthly users' },
  { value: 'active_users', label: 'Active users' },
  { value: 'avg_session_seconds', label: 'Session time' },
  { value: 'installs', label: 'Installs' },
  { value: 'uninstalls', label: 'Uninstalls' },
  { value: 'retention_d1', label: 'Retention D1' },
  { value: 'retention_d7', label: 'Retention D7' },
  { value: 'retention_d30', label: 'Retention D30' },
];

const metricLabel = computed(
  () => metricOptions.find((option) => option.value === metric.value)?.label || metric.value,
);

const changeValue = computed(() => analyticsStore.trends?.change_percent ?? 0);

const summaryCards = computed(() => [
  {
    label: 'Current total',
    value: formatNumber(analyticsStore.trends?.current_total),
    icon: ChartBarIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
    valueClass: 'text-slate-900',
  },
  {
    label: 'Previous total',
    value: formatNumber(analyticsStore.trends?.previous_total),
    icon: ChartBarIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-600',
    valueClass: 'text-slate-900',
  },
  {
    label: 'Change',
    value: `${changeValue.value}%`,
    icon: changeValue.value >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon,
    iconBg: changeValue.value >= 0 ? 'bg-emerald-50' : 'bg-rose-50',
    iconColor: changeValue.value >= 0 ? 'text-emerald-600' : 'text-rose-600',
    valueClass:
      changeValue.value > 0
        ? 'text-emerald-700'
        : changeValue.value < 0
          ? 'text-rose-700'
          : 'text-slate-900',
  },
]);

function isActive(name) {
  return route.name === name;
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString();
}

watch(
  () => analyticsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load trend analytics');
  },
);

onMounted(reload);

async function reload() {
  await analyticsStore.fetchTrends(route.params.id, { metric: metric.value });
}
</script>
