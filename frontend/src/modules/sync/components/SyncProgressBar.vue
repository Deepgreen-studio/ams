<template>
  <div class="w-full">
    <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
      <span class="capitalize">{{ statusLabel }}</span>
      <span>{{ percent }}%</span>
    </div>
    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
      <div
        class="h-full rounded-full transition-all duration-300"
        :class="barClass"
        :style="{ width: `${percent}%` }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  percent: { type: Number, default: 0 },
  status: { type: String, default: 'pending' },
});

const percent = computed(() => Math.min(100, Math.max(0, Number(props.percent) || 0)));
const statusLabel = computed(() => props.status || 'pending');
const barClass = computed(() => {
  if (props.status === 'failed') return 'bg-rose-500';
  if (props.status === 'completed') return 'bg-emerald-500';
  if (props.status === 'running' || props.status === 'queued') return 'bg-brand-500';
  return 'bg-slate-400';
});
</script>
