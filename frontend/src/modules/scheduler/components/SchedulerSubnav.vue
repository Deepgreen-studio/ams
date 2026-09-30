<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Scheduler sections"
    >
      <RouterLink
        v-for="item in visibleItems"
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
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';

const route = useRoute();
const { can } = usePermissions();

const items = [
  { name: 'scheduler.dashboard', label: 'Dashboard', to: { name: 'scheduler.dashboard' }, match: ['scheduler.dashboard'], permission: 'scheduler.view' },
  {
    name: 'scheduler.jobs',
    label: 'Jobs',
    to: { name: 'scheduler.jobs' },
    match: ['scheduler.jobs', 'scheduler.jobs.create', 'scheduler.jobs.edit'],
    permission: 'scheduler.jobs',
  },
  { name: 'scheduler.history', label: 'History', to: { name: 'scheduler.history' }, match: ['scheduler.history'], permission: 'scheduler.history' },
  { name: 'scheduler.running', label: 'Running', to: { name: 'scheduler.running' }, match: ['scheduler.running'], permission: 'scheduler.running' },
  { name: 'scheduler.failed', label: 'Failed', to: { name: 'scheduler.failed' }, match: ['scheduler.failed'], permission: 'scheduler.failed' },
  { name: 'scheduler.logs', label: 'Logs', to: { name: 'scheduler.logs' }, match: ['scheduler.logs'], permission: 'scheduler.logs' },
  { name: 'scheduler.statistics', label: 'Statistics', to: { name: 'scheduler.statistics' }, match: ['scheduler.statistics'], permission: 'scheduler.statistics' },
];

const visibleItems = computed(() => items.filter((item) => can(item.permission)));

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
