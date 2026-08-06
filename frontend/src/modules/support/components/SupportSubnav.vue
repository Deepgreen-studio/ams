<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        isActive(item.name)
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
    return route.name === 'support.tickets.index' || route.name === 'support.tickets.show';
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
