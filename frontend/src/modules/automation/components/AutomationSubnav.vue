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
  { name: 'automation.dashboard', label: 'Dashboard', to: { name: 'automation.dashboard' }, match: ['automation.dashboard'] },
  { name: 'automation.rules', label: 'Rules', to: { name: 'automation.rules' }, match: ['automation.rules', 'automation.rules.create', 'automation.rules.edit'] },
  { name: 'automation.history', label: 'History', to: { name: 'automation.history' }, match: ['automation.history'] },
];

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
