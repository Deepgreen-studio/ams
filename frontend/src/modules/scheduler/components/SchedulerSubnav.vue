<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        item.match.includes(route.name)
          ? 'bg-brand-50 text-brand-700'
          : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
      "
    >
      {{ item.label }}
    </RouterLink>
  </div>
</template>

<script setup>
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  { name: 'scheduler.dashboard', label: 'Dashboard', to: { name: 'scheduler.dashboard' }, match: ['scheduler.dashboard'] },
  { name: 'scheduler.jobs', label: 'Jobs', to: { name: 'scheduler.jobs' }, match: ['scheduler.jobs', 'scheduler.jobs.create', 'scheduler.jobs.edit'] },
  { name: 'scheduler.history', label: 'History', to: { name: 'scheduler.history' }, match: ['scheduler.history'] },
  { name: 'scheduler.running', label: 'Running', to: { name: 'scheduler.running' }, match: ['scheduler.running'] },
  { name: 'scheduler.failed', label: 'Failed', to: { name: 'scheduler.failed' }, match: ['scheduler.failed'] },
  { name: 'scheduler.logs', label: 'Logs', to: { name: 'scheduler.logs' }, match: ['scheduler.logs'] },
  { name: 'scheduler.statistics', label: 'Statistics', to: { name: 'scheduler.statistics' }, match: ['scheduler.statistics'] },
];
</script>
