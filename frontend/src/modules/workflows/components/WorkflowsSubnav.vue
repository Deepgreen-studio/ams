<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        isActive(item)
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
  { name: 'workflows.dashboard', label: 'Dashboard', match: ['workflows.dashboard'] },
  { name: 'workflows.designer', label: 'Designer', match: ['workflows.designer', 'workflows.designer.create', 'workflows.designer.edit'] },
  { name: 'workflows.monitor', label: 'Monitor', match: ['workflows.monitor', 'workflows.instances.show'] },
  { name: 'workflows.queue', label: 'Approval Queue', match: ['workflows.queue'] },
  { name: 'workflows.history', label: 'History', match: ['workflows.history'] },
];

items.forEach((item) => {
  item.to = { name: item.name === 'workflows.designer' ? 'workflows.designer' : item.name };
});

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
