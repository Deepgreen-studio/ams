<template>
  <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 sm:p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
      <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
      <span v-if="subtitle" class="text-xs text-slate-500">{{ subtitle }}</span>
    </div>
    <div v-if="!points.length" class="py-12 text-center text-sm text-slate-500">
      No chart data.
    </div>
    <svg
      v-else
      :viewBox="`0 0 ${width} ${height}`"
      class="h-44 w-full"
      preserveAspectRatio="none"
    >
      <polygon v-if="filled" :points="area" :fill="fill" opacity="0.12" />
      <polyline fill="none" :stroke="stroke" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" :points="polyline" />
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
  stroke: { type: String, default: '#f97316' },
  fill: { type: String, default: '#f97316' },
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
    .join(' ');
});

const area = computed(() => {
  if (!polyline.value) return '';
  const firstX = pad;
  const lastX =
    pad + ((values.value.length - 1) * (width - pad * 2)) / Math.max(1, values.value.length - 1);
  return `${firstX},${height - pad} ${polyline.value} ${lastX},${height - pad}`;
});
</script>
