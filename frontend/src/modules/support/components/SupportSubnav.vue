<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Support sections">
      <RouterLink
        v-for="item in visibleItems"
        :key="item.name"
        :to="item.to"
        class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
        :class="
          isActive(item.name)
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
  { name: 'support.dashboard', label: 'Dashboard', to: { name: 'support.dashboard' }, permission: 'support.view' },
  { name: 'support.tickets.index', label: 'Tickets', to: { name: 'support.tickets.index' }, permission: 'support.tickets' },
  { name: 'support.tickets.board', label: 'Kanban', to: { name: 'support.tickets.board' }, permission: 'support.board' },
  { name: 'support.tickets.queue', label: 'Queue', to: { name: 'support.tickets.queue' }, permission: 'support.queue' },
  { name: 'support.tickets.assignment', label: 'Assignment', to: { name: 'support.tickets.assignment' }, permission: 'support.assignment' },
  { name: 'support.sla.dashboard', label: 'SLA', to: { name: 'support.sla.dashboard' }, permission: 'support.sla' },
  { name: 'support.knowledge.center', label: 'Knowledge', to: { name: 'support.knowledge.center' }, permission: 'support.knowledge' },
  { name: 'support.canned.index', label: 'Canned', to: { name: 'support.canned.index' }, permission: 'support.canned' },
  { name: 'support.tickets.create', label: 'Create', to: { name: 'support.tickets.create' }, permission: 'support.create' },
];

const visibleItems = computed(() => items.filter((item) => can(item.permission)));

function isActive(name) {
  if (name === 'support.tickets.index') {
    return (
      route.name === 'support.tickets.index' ||
      route.name === 'support.tickets.show' ||
      route.name === 'support.tickets.edit'
    );
  }
  if (name === 'support.sla.dashboard') {
    return String(route.name || '').startsWith('support.sla.');
  }
  if (name === 'support.knowledge.center') {
    return String(route.name || '').startsWith('support.knowledge.');
  }
  if (name === 'support.canned.index') {
    return String(route.name || '').startsWith('support.canned.');
  }
  return route.name === name;
}
</script>
