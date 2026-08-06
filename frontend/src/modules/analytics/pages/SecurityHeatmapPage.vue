<template>
  <div>
    <PageHeader title="Activity Heatmap" description="Login activity intensity by weekday and hour." />
    <AnalyticsSubnav />
    <SecurityAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        From
        <input v-model="filters.from" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        To
        <input v-model="filters.to" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !cells.length" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-4">
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
            class="aspect-square rounded-sm"
            :title="`${day.label} ${hour - 1}:00 — ${cellCount(day.value, hour - 1)} events`"
            :style="{ backgroundColor: cellColor(day.value, hour - 1) }"
          />
        </div>
      </div>
      <p class="mt-4 text-xs text-slate-500">Darker cells indicate higher login volume.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SecurityAnalyticsSubnav from '@/modules/analytics/components/SecurityAnalyticsSubnav.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();

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

const maxCount = computed(() => Math.max(1, ...cells.value.map((c) => Number(c.count) || 0)));

const lookup = computed(() => {
  const map = {};
  for (const cell of cells.value) {
    map[`${cell.weekday}-${cell.hour}`] = Number(cell.count) || 0;
  }
  return map;
});

function cellCount(weekday, hour) {
  return lookup.value[`${weekday}-${hour}`] || 0;
}

function cellColor(weekday, hour) {
  const count = cellCount(weekday, hour);
  if (!count) return '#f1f5f9';
  const intensity = Math.min(1, count / maxCount.value);
  const alpha = 0.15 + intensity * 0.85;
  return `rgba(15, 118, 110, ${alpha.toFixed(2)})`;
}

async function load() {
  await store.fetchHeatmap({ ...filters });
}

onMounted(load);
</script>
