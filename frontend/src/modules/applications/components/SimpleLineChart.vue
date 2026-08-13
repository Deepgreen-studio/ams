<template>
  <div :class="framed ? 'rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 sm:p-6' : 'h-full'">
    <div v-if="framed || title || hint" class="mb-4 flex items-center justify-between gap-2">
      <h3 v-if="title" class="text-base font-semibold text-slate-900">{{ title }}</h3>
      <p v-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    </div>
    <div
      v-if="!normalizedPoints.length"
      class="flex h-40 flex-col items-center justify-center rounded-[12px] bg-zinc-50 text-center"
    >
      <p class="text-sm font-medium text-slate-700">No chart data</p>
      <p class="mt-1 text-xs text-slate-500">Refresh the snapshot to populate trends.</p>
    </div>
    <svg v-else :viewBox="`0 0 ${width} ${height}`" class="h-44 w-full" :class="{ 'h-full max-h-44': !framed }" role="img">
      <line
        v-for="(tick, index) in yTicks"
        :key="`grid-${index}`"
        :x1="padding"
        :x2="width - padding"
        :y1="tick.y"
        :y2="tick.y"
        class="stroke-slate-100"
        stroke-width="1"
      />
      <polyline
        v-for="(series, seriesIndex) in normalizedSeries"
        :key="series.key"
        fill="none"
        :stroke="colors[seriesIndex % colors.length]"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        :points="series.points"
      />
      <g v-for="(label, index) in labels" :key="`label-${index}`">
        <text
          v-if="index % labelStep === 0"
          :x="xFor(index)"
          :y="height - 8"
          text-anchor="middle"
          class="fill-slate-400 text-[10px]"
        >
          {{ shortLabel(label) }}
        </text>
      </g>
    </svg>
    <div v-if="seriesMeta.length" class="mt-2 flex flex-wrap gap-3">
      <div
        v-for="(item, index) in seriesMeta"
        :key="item.key"
        class="flex items-center gap-1.5 text-xs text-slate-600"
      >
        <span
          class="inline-block h-2.5 w-2.5 rounded-full"
          :style="{ backgroundColor: colors[index % colors.length] }"
        />
        {{ item.label }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Chart' },
  hint: { type: String, default: '' },
  labels: { type: Array, default: () => [] },
  series: { type: Array, default: () => [] },
  framed: { type: Boolean, default: true },
});

const width = 640;
const height = 180;
const padding = 24;
const colors = ['#0f766e', '#0369a1', '#b45309', '#be123c', '#7c3aed'];

const seriesMeta = computed(() => props.series.filter((item) => Array.isArray(item.values)));
const normalizedPoints = computed(() => seriesMeta.value.flatMap((item) => item.values || []));

const maxValue = computed(() => {
  const values = normalizedPoints.value.map((value) => Number(value) || 0);
  return Math.max(1, ...values);
});

const labelStep = computed(() => Math.max(1, Math.ceil((props.labels.length || 1) / 6)));

const yTicks = computed(() =>
  [0.25, 0.5, 0.75, 1].map((ratio) => ({
    y: padding + (1 - ratio) * (height - padding * 2),
  })),
);

const normalizedSeries = computed(() =>
  seriesMeta.value.map((item) => ({
    key: item.key,
    points: (item.values || [])
      .map((value, index) => {
        const x = xFor(index);
        const y = padding + (1 - (Number(value) || 0) / maxValue.value) * (height - padding * 2);
        return `${x},${y}`;
      })
      .join(''),
  })),
);

function xFor(index) {
  const count = Math.max(props.labels.length - 1, 1);
  return padding + (index / count) * (width - padding * 2);
}

function shortLabel(value) {
  if (!value) return '';
  const text = String(value);
  return text.length > 10 ? text.slice(5, 10) : text;
}
</script>
