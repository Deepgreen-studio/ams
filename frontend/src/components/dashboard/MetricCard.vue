<template>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-100">
    <div class="flex items-start justify-between gap-3">
      <div
        class="inline-flex h-11 w-11 items-center justify-center rounded-full"
        :class="iconBg"
      >
        <component :is="icon" class="h-5 w-5" :class="iconColor" />
      </div>
      <span
        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
        :class="favorable ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
      >
        <ArrowTrendingUpIcon v-if="trendUp" class="h-3.5 w-3.5" />
        <ArrowTrendingDownIcon v-else class="h-3.5 w-3.5" />
        {{ trendLabel }}
      </span>
    </div>
    <p class="mt-4 text-sm font-medium text-zinc-500">{{ label }}</p>
    <p class="mt-1 text-2xl font-bold tracking-tight text-zinc-900">{{ value }}</p>
    <p class="mt-1 text-xs text-zinc-400">{{ hint || secondary }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ArrowTrendingDownIcon, ArrowTrendingUpIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: String, required: true },
  hint: { type: String, default: '' },
  secondary: { type: String, default: '' },
  trendLabel: { type: String, required: true },
  trendUp: { type: Boolean, default: true },
  trendFavorable: { type: Boolean, default: undefined },
  icon: { type: [Object, Function], required: true },
  iconBg: { type: String, default: 'bg-brand-50' },
  iconColor: { type: String, default: 'text-brand-500' },
});

const favorable = computed(() =>
  props.trendFavorable === undefined ? props.trendUp : props.trendFavorable,
);
</script>
