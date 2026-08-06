<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="mb-3 flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
      <p v-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    </div>
    <div
      v-if="!items.length"
      class="flex h-40 items-center justify-center text-sm text-slate-500"
    >
      No chart data
    </div>
    <div v-else class="space-y-3">
      <div v-for="item in items" :key="item.label" class="space-y-1">
        <div class="flex items-center justify-between text-xs text-slate-600">
          <span class="capitalize">{{ item.label }}</span>
          <span class="font-medium text-slate-900">{{ item.value }}</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
          <div
            class="h-full rounded-full bg-brand-600 transition-all"
            :style="{ width: `${barWidth(item.value)}%` }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Distribution' },
  hint: { type: String, default: '' },
  data: { type: Object, default: () => ({}) },
});

const items = computed(() =>
  Object.entries(props.data || {})
    .map(([label, value]) => ({ label: String(label).replaceAll('_', ' '), value: Number(value) || 0 }))
    .sort((a, b) => b.value - a.value)
);

const maxValue = computed(() => Math.max(1, ...items.value.map((item) => item.value)));

function barWidth(value) {
  return Math.max(4, Math.round((Number(value) / maxValue.value) * 100));
}
</script>
