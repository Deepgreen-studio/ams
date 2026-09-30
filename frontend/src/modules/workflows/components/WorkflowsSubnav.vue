<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Workflow Engine sections"
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
  {
    name: 'workflows.dashboard',
    label: 'Dashboard',
    to: { name: 'workflows.dashboard' },
    match: ['workflows.dashboard'],
    permission: 'workflows.view',
  },
  {
    name: 'workflows.designer',
    label: 'Designer',
    to: { name: 'workflows.designer' },
    match: ['workflows.designer', 'workflows.designer.create', 'workflows.designer.edit'],
    permission: 'workflows.designer',
  },
  {
    name: 'workflows.monitor',
    label: 'Monitor',
    to: { name: 'workflows.monitor' },
    match: ['workflows.monitor', 'workflows.instances.show'],
    permission: 'workflows.monitor',
  },
  {
    name: 'workflows.queue',
    label: 'Approval Queue',
    to: { name: 'workflows.queue' },
    match: ['workflows.queue'],
    permission: 'workflows.approve',
  },
  {
    name: 'workflows.history',
    label: 'History',
    to: { name: 'workflows.history' },
    match: ['workflows.history'],
    permission: 'workflows.history',
  },
];

const visibleItems = computed(() => items.filter((item) => can(item.permission)));

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
