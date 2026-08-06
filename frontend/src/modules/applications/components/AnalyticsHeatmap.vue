<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="mb-3 flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
      <p v-if="max" class="text-xs text-slate-500">Max activity: {{ max }}</p>
    </div>
    <div
      v-if="!matrix?.length"
      class="flex h-48 items-center justify-center text-sm text-slate-500"
    >
      No heatmap data
    </div>
    <div v-else class="overflow-x-auto">
      <div class="inline-grid gap-1" :style="gridStyle">
        <div class="h-6" />
        <div
          v-for="hour in hours"
          :key="`h-${hour}`"
          class="flex h-6 w-5 items-end justify-center text-[9px] text-slate-400"
        >
          {{ hour % 3 === 0 ? hour : '' }}
        </div>
        <template v-for="(dayLabel, dayIndex) in days" :key="`day-${dayIndex}`">
          <div class="flex h-5 items-center pr-2 text-[10px] font-medium text-slate-500">
            {{ dayLabel }}
          </div>
          <button
            v-for="(value, hour) in matrix[dayIndex] || []"
            :key="`${dayIndex}-${hour}`"
            type="button"
            class="h-5 w-5 rounded-sm"
            :style="{ backgroundColor: cellColor(value) }"
            :title="`${dayLabel} ${hour}:00 — ${value}`"
          />
        </template>
      </div>
    </div>
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

const gridStyle = computed(() => ({
  gridTemplateColumns: `auto repeat(${props.hours.length || 24}, 1.25rem)`,
}));

function cellColor(value) {
  const max = Math.max(1, props.max || 1);
  const intensity = Math.min(1, (Number(value) || 0) / max);
  const alpha = 0.08 + intensity * 0.85;
  return `rgba(15, 118, 110, ${alpha})`;
}
</script>
