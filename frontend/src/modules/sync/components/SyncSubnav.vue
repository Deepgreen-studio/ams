<template>
  <nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
      active-class="bg-brand-50 text-brand-700 hover:bg-brand-50"
    >
      {{ item.label }}
    </RouterLink>
  </nav>
</template>

<script setup>
import { RouterLink } from 'vue-router';

const items = [
  { name: 'sync.dashboard', label: 'Dashboard', to: { name: 'sync.dashboard' } },
  { name: 'sync.configs', label: 'Configs', to: { name: 'sync.configs' } },
  { name: 'sync.history', label: 'History', to: { name: 'sync.history' } },
  { name: 'sync.logs', label: 'Logs', to: { name: 'sync.logs' } },
];
</script>
