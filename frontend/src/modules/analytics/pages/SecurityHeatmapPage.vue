<template>
  <div>
    <AnalyticsSubnav />

    <EnterpriseFilterBar v-model="filters" :show-category="false" @apply="onApply" @reset="onApply" />

    <div v-if="store.loading && !cells.length" class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="store.error && !cells.length"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load activity heatmap</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading login intensity by weekday and hour again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <template v-else>
      <div class="mb-4 grid gap-4 sm:grid-cols-2">
        <div class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Peak intensity</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ formatNumber(maxCount) }}</p>
            <p class="mt-1 text-xs text-slate-400">Highest login volume in a single hour</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-brand-50">
            <FireIcon class="h-5 w-5 text-brand-500" />
          </div>
        </div>
        <div class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active cells</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ formatNumber(activeCells) }}</p>
            <p class="mt-1 text-xs text-slate-400">Weekday / hour slots with logins</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-zinc-100">
            <Squares2X2Icon class="h-5 w-5 text-slate-500" />
          </div>
        </div>
      </div>

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
          <h2 class="text-base font-semibold text-slate-900">Login activity heatmap</h2>
          <p class="mt-0.5 text-xs text-slate-500">Darker cells indicate higher login volume by weekday and hour.</p>
        </div>

        <div v-if="!cells.length" class="px-6 py-16 text-center sm:px-8">
          <p class="text-sm font-medium text-slate-900">No login activity in this period</p>
          <p class="mt-1 text-xs text-slate-500">Adjust the date range to inspect another window.</p>
        </div>
        <div v-else class="overflow-x-auto px-6 py-5 sm:px-8">
          <div class="min-w-[720px]">
            <div class="mb-2 grid grid-cols-[64px_repeat(24,minmax(0,1fr))] gap-1 text-center text-[10px] text-slate-400">
              <span />
              <span v-for="h in 24" :key="h">{{ h - 1 }}</span>
            </div>
            <div v-for="day in days" :key="day.value" class="mb-1 grid grid-cols-[64px_repeat(24,minmax(0,1fr))] gap-1">
              <span class="flex items-center text-xs text-slate-500">{{ day.label }}</span>
              <div
                v-for="hour in 24"
                :key="hour"
                class="aspect-square rounded-[4px]"
                :title="`${day.label} ${hour - 1}:00 — ${cellCount(day.value, hour - 1)} events`"
                :style="{ backgroundColor: cellColor(day.value, hour - 1) }"
              />
            </div>
          </div>
          <p class="mt-4 text-xs text-slate-500">Hover a cell to see the exact event count.</p>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { FireIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const toast = useToast();

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const days = [
  { value: 0, label: 'Sun' },
  { value: 1, label: 'Mon' },
  { value: 2, label: 'Tue' },
  { value: 3, label: 'Wed' },
  { value: 4, label: 'Thu' },
  { value: 5, label: 'Fri' },
  { value: 6, label: 'Sat' },
];

const cells = computed(() => store.heatmap?.heatmap || []);
const maxCount = computed(() => Math.max(0, ...cells.value.map((c) => Number(c.count) || 0)));
const activeCells = computed(() => cells.value.filter((c) => Number(c.count) > 0).length);

const lookup = computed(() => {
  const map = {};
  for (const cell of cells.value) {
    map[`${cell.weekday}-${cell.hour}`] = Number(cell.count) || 0;
  }
  return map;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.heatmap) return;
    toast.error(message);
    store.error = null;
  },
);

function cellCount(weekday, hour) {
  return lookup.value[`${weekday}-${hour}`] || 0;
}

function cellColor(weekday, hour) {
  const count = cellCount(weekday, hour);
  if (!count) return '#f4f4f5';
  const intensity = Math.min(1, count / Math.max(1, maxCount.value));
  const alpha = 0.15 + intensity * 0.85;
  return `rgba(15, 118, 110, ${alpha.toFixed(2)})`;
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

function load() {
  store.fetchHeatmap({ ...filters }).catch(() => {});
}

onMounted(() => {
  store.error = null;
  load();
});
</script>
