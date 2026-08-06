<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="mb-3 flex items-center justify-between gap-3">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ title }}</h3>
      <span v-if="subtitle" class="text-xs text-slate-500">{{ subtitle }}</span>
    </div>
    <div v-if="!points.length" class="py-10 text-center text-sm text-slate-500">No chart data.</div>
    <svg v-else :viewBox="`0 0 ${width} ${height}`" class="h-40 w-full" preserveAspectRatio="none">
      <polyline fill="none" :stroke="stroke" stroke-width="2" :points="polyline" />
      <polygon v-if="filled" :points="area" :fill="fill" opacity="0.15" />
    </svg>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Chart' },
  subtitle: { type: String, default: '' },
  points: { type: Array, default: () => [] },
  valueKey: { type: String, default: 'value' },
  stroke: { type: String, default: '#2563eb' },
  fill: { type: String, default: '#2563eb' },
  filled: { type: Boolean, default: true },
});

const width = 320;
const height = 120;
const pad = 8;

const values = computed(() => props.points.map((p) => Number(p[props.valueKey] ?? 0)));
const max = computed(() => Math.max(1, ...values.value));
const min = computed(() => Math.min(0, ...values.value));

const polyline = computed(() => {
  if (!values.value.length) return '';
  return values.value
    .map((value, index) => {
      const x = pad + (index * (width - pad * 2)) / Math.max(1, values.value.length - 1);
      const y =
        height - pad - ((value - min.value) / (max.value - min.value || 1)) * (height - pad * 2);
      return `${x},${y}`;
    })
    .join('');
});

const area = computed(() => {
  if (!polyline.value) return '';
  const firstX = pad;
  const lastX =
    pad + ((values.value.length - 1) * (width - pad * 2)) / Math.max(1, values.value.length - 1);
  return `${firstX},${height - pad} ${polyline.value} ${lastX},${height - pad}`;
});
</script>
