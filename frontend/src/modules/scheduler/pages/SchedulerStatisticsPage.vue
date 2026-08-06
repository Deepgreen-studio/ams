<template>
  <div>
    <PageHeader title="Scheduler Statistics" description="Aggregated job definition and run metrics." />
    <SchedulerSubnav />

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in jobCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="card in runCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();

const jobCards = computed(() => [
  { label: 'Total jobs', value: store.statistics?.total ?? 0 },
  { label: 'Enabled', value: store.statistics?.enabled ?? 0 },
  { label: 'One-time', value: store.statistics?.one_time ?? 0 },
  { label: 'Queue jobs', value: store.statistics?.queue ?? 0 },
]);

const runCards = computed(() => [
  { label: 'Total runs', value: store.runStatistics?.total ?? 0 },
  { label: 'Queued', value: store.runStatistics?.queued ?? 0 },
  { label: 'Running', value: store.runStatistics?.running ?? 0 },
  { label: 'Success', value: store.runStatistics?.success ?? 0 },
  { label: 'Failed', value: store.runStatistics?.failed ?? 0 },
  { label: 'Pending', value: store.runStatistics?.pending ?? 0 },
]);

onMounted(() => store.fetchStatistics());
</script>
