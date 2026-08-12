<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Scheduler sections"
    >
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="item.to"
        class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
        :class="
          isActive(item)
            ? 'border-brand-600 text-brand-700'
            : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'
        "
      >
        {{ item.label }}
      </RouterLink>
    </nav>
  </div>
</template>

<script setup>
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  { name: 'scheduler.dashboard', label: 'Dashboard', to: { name: 'scheduler.dashboard' }, match: ['scheduler.dashboard'] },
  {
    name: 'scheduler.jobs',
    label: 'Jobs',
    to: { name: 'scheduler.jobs' },
    match: ['scheduler.jobs', 'scheduler.jobs.create', 'scheduler.jobs.edit'],
  },
  { name: 'scheduler.history', label: 'History', to: { name: 'scheduler.history' }, match: ['scheduler.history'] },
  { name: 'scheduler.running', label: 'Running', to: { name: 'scheduler.running' }, match: ['scheduler.running'] },
  { name: 'scheduler.failed', label: 'Failed', to: { name: 'scheduler.failed' }, match: ['scheduler.failed'] },
  { name: 'scheduler.logs', label: 'Logs', to: { name: 'scheduler.logs' }, match: ['scheduler.logs'] },
  { name: 'scheduler.statistics', label: 'Statistics', to: { name: 'scheduler.statistics' }, match: ['scheduler.statistics'] },
];

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
