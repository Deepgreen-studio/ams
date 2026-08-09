<template>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-100">
    <h2 class="text-base font-semibold text-zinc-900">Overall progress</h2>

    <div class="relative mx-auto mt-4 flex h-44 w-full max-w-[240px] items-end justify-center">
      <svg viewBox="0 0 200 120" class="h-full w-full" aria-hidden="true">
        <defs>
          <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#22c55e" />
            <stop offset="50%" stop-color="#eab308" />
            <stop offset="100%" stop-color="#ff5c00" />
          </linearGradient>
        </defs>
        <path
          d="M20 110 A80 80 0 0 1 180 110"
          fill="none"
          stroke="#f4f4f5"
          stroke-width="16"
          stroke-linecap="round"
        />
        <path
          d="M20 110 A80 80 0 0 1 180 110"
          fill="none"
          stroke="url(#gaugeGrad)"
          stroke-width="16"
          stroke-linecap="round"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="circumference - (circumference * percent) / 100"
        />
        <circle :cx="needle.x" :cy="needle.y" r="7" fill="#18181b" />
        <circle :cx="needle.x" :cy="needle.y" r="3.5" fill="#fff" />
      </svg>
      <div class="pointer-events-none absolute inset-x-0 bottom-2 text-center">
        <p class="text-3xl font-bold text-zinc-900">{{ percent }}%</p>
        <p class="text-xs font-medium text-zinc-500">Completed</p>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
      <div v-for="stat in stats" :key="stat.label" class="rounded-xl bg-zinc-50 px-3 py-2.5">
        <p class="text-xs text-zinc-500">{{ stat.label }}</p>
        <p class="mt-0.5 text-lg font-bold" :class="stat.color">{{ stat.value }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const percent = 72;
const circumference = Math.PI * 80;

const needle = computed(() => {
  const angle = Math.PI * (1 - percent / 100);
  const cx = 100;
  const cy = 110;
  const r = 80;
  return {
    x: cx + r * Math.cos(angle),
    y: cy - r * Math.sin(angle),
  };
});

const stats = [
  { label: 'Total apps', value: 95, color: 'text-zinc-900' },
  { label: 'Completed', value: 26, color: 'text-emerald-600' },
  { label: 'Delayed', value: 35, color: 'text-amber-600' },
  { label: 'On going', value: 35, color: 'text-brand-500' },
];
</script>
