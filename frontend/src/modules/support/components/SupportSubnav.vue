<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Support sections">
      <RouterLink
        v-for="item in items"
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
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  { name: 'support.dashboard', label: 'Dashboard', to: { name: 'support.dashboard' } },
  { name: 'support.tickets.index', label: 'Tickets', to: { name: 'support.tickets.index' } },
  { name: 'support.tickets.board', label: 'Kanban', to: { name: 'support.tickets.board' } },
  { name: 'support.tickets.queue', label: 'Queue', to: { name: 'support.tickets.queue' } },
  { name: 'support.tickets.assignment', label: 'Assignment', to: { name: 'support.tickets.assignment' } },
  { name: 'support.sla.dashboard', label: 'SLA', to: { name: 'support.sla.dashboard' } },
  { name: 'support.knowledge.center', label: 'Knowledge', to: { name: 'support.knowledge.center' } },
  { name: 'support.canned.index', label: 'Canned', to: { name: 'support.canned.index' } },
  { name: 'support.tickets.create', label: 'Create', to: { name: 'support.tickets.create' } },
];

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
