<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Sync sections">
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
  {
    name: 'sync.dashboard',
    label: 'Dashboard',
    to: { name: 'sync.dashboard' },
    match: ['sync.dashboard'],
  },
  {
    name: 'sync.configs',
    label: 'Configs',
    to: { name: 'sync.configs' },
    match: ['sync.configs', 'sync.configs.create', 'sync.configs.show', 'sync.configs.edit'],
  },
  {
    name: 'sync.history',
    label: 'History',
    to: { name: 'sync.history' },
    match: ['sync.history'],
  },
  {
    name: 'sync.logs',
    label: 'Logs',
    to: { name: 'sync.logs' },
    match: ['sync.logs'],
  },
];

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
