<template>
  <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
    <p class="mt-1 font-mono text-sm font-semibold" :class="tone">
      {{ display }}
    </p>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  dueAt: { type: [String, Date], default: null },
  completedAt: { type: [String, Date], default: null },
  remainingSeconds: { type: Number, default: null },
});

const now = ref(Date.now());
let timer;

onMounted(() => {
  timer = window.setInterval(() => {
    now.value = Date.now();
  }, 1000);
});

onUnmounted(() => {
  if (timer) window.clearInterval(timer);
});

watch(
  () => [props.dueAt, props.remainingSeconds, props.completedAt],
  () => {
    now.value = Date.now();
  }
);

const secondsLeft = computed(() => {
  if (props.completedAt) return null;
  if (props.remainingSeconds !== null && props.remainingSeconds !== undefined) {
    const dueMs = props.dueAt ? new Date(props.dueAt).getTime() : null;
    if (dueMs) {
      return Math.floor((dueMs - now.value) / 1000);
    }
    return props.remainingSeconds;
  }
  if (!props.dueAt) return null;
  return Math.floor((new Date(props.dueAt).getTime() - now.value) / 1000);
});

const display = computed(() => {
  if (props.completedAt) return 'Completed';
  if (secondsLeft.value === null) return '—';
  const abs = Math.abs(secondsLeft.value);
  const hours = Math.floor(abs / 3600);
  const minutes = Math.floor((abs % 3600) / 60);
  const seconds = abs % 60;
  const pad = (n) => String(n).padStart(2, '0');
  const base = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
  return secondsLeft.value < 0 ? `-${base}` : base;
});

const tone = computed(() => {
  if (props.completedAt) return 'text-emerald-700';
  if (secondsLeft.value === null) return 'text-slate-500';
  if (secondsLeft.value < 0) return 'text-rose-600';
  if (secondsLeft.value < 3600) return 'text-amber-700';
  return 'text-slate-900';
});
</script>
