<template>
  <div class="rounded-[12px] bg-white p-5 sm:p-6 ring-1 ring-zinc-100">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
        <p class="mt-0.5 text-xs text-slate-500">Session activity by day and hour</p>
      </div>
      <p v-if="hasActivity" class="text-xs text-slate-500">
        Max activity: <span class="font-semibold text-slate-700">{{ max }}</span>
      </p>
    </div>

    <div
      v-if="!hasActivity"
      class="flex h-56 flex-col items-center justify-center rounded-[12px] bg-zinc-50 text-center"
    >
      <p class="text-sm font-medium text-slate-700">No heatmap data</p>
      <p class="mt-1 max-w-sm text-xs text-slate-500">
        Ingest session analytics with heatmap buckets to populate activity intensity.
      </p>
    </div>

    <template v-else>
      <div class="overflow-x-auto">
        <div class="inline-grid gap-1.5" :style="gridStyle">
          <div class="h-7" />
          <div
            v-for="hour in hours"
            :key="`h-${hour}`"
            class="flex h-7 w-6 items-end justify-center pb-0.5 text-[10px] text-slate-400"
          >
            {{ hour % 3 === 0 ? hour : '' }}
          </div>
          <template v-for="(dayLabel, dayIndex) in days" :key="`day-${dayIndex}`">
            <div class="flex h-6 items-center pr-2 text-[11px] font-medium text-slate-500">
              {{ dayLabel }}
            </div>
            <button
              v-for="(value, hour) in matrix[dayIndex] || []"
              :key="`${dayIndex}-${hour}`"
              type="button"
              class="h-6 w-6 rounded-[6px] transition hover:ring-2 hover:ring-brand-200 focus:outline-none focus:ring-2 focus:ring-brand-300"
              :style="{ backgroundColor: cellColor(value) }"
              :title="`${dayLabel} ${padHour(hour)}:00 — ${value} sessions`"
            />
          </template>
        </div>
      </div>

      <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4">
        <p class="text-xs text-slate-500">Hover a cell for exact activity count</p>
        <div class="flex items-center gap-2 text-[11px] text-slate-500">
          <span>Less</span>
          <span
            v-for="step in legendSteps"
            :key="step"
            class="inline-block h-3.5 w-3.5 rounded-[4px]"
            :style="{ backgroundColor: cellColor(step) }"
          />
          <span>More</span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Activity heatmap' },
  days: { type: Array, default: () => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] },
  hours: { type: Array, default: () => Array.from({ length: 24 }, (_, i) => i) },
  matrix: { type: Array, default: () => [] },
  max: { type: Number, default: 0 },
});

const hasActivity = computed(() => Number(props.max) > 0);

const legendSteps = computed(() => {
  const peak = Math.max(1, Number(props.max) || 1);
  return [0, peak * 0.25, peak * 0.5, peak * 0.75, peak];
});

const gridStyle = computed(() => ({
  gridTemplateColumns: `auto repeat(${props.hours.length || 24}, 1.5rem)`,
}));

function padHour(hour) {
  return String(hour).padStart(2, '0');
}

function cellColor(value) {
  const amount = Number(value) || 0;
  if (amount <= 0) {
    return '#f4f4f5'; // zinc-100 — idle cells stay neutral
  }

  const peak = Math.max(1, Number(props.max) || 1);
  const intensity = Math.min(1, amount / peak);
  const alpha = 0.2 + intensity * 0.8;
  return `rgba(234, 88, 12, ${alpha})`;
}
</script>
