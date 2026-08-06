<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
      :class="
        item.match.includes(route.name)
          ? 'bg-slate-900 text-white'
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
  { name: 'analytics.executive', label: 'CEO', to: { name: 'analytics.executive' }, match: ['analytics.executive'] },
  { name: 'analytics.executive.admin', label: 'Admin', to: { name: 'analytics.executive.admin' }, match: ['analytics.executive.admin'] },
  { name: 'analytics.executive.operations', label: 'Operations', to: { name: 'analytics.executive.operations' }, match: ['analytics.executive.operations'] },
  { name: 'analytics.executive.compliance', label: 'Compliance', to: { name: 'analytics.executive.compliance' }, match: ['analytics.executive.compliance'] },
  { name: 'analytics.executive.support', label: 'Support', to: { name: 'analytics.executive.support' }, match: ['analytics.executive.support'] },
  { name: 'analytics.executive.customer', label: 'Customer', to: { name: 'analytics.executive.customer' }, match: ['analytics.executive.customer'] },
  { name: 'analytics.executive.scorecards', label: 'Scorecards', to: { name: 'analytics.executive.scorecards' }, match: ['analytics.executive.scorecards'] },
  { name: 'analytics.executive.trends', label: 'Trends', to: { name: 'analytics.executive.trends' }, match: ['analytics.executive.trends'] },
  { name: 'analytics.executive.forecast', label: 'Forecast', to: { name: 'analytics.executive.forecast' }, match: ['analytics.executive.forecast'] },
];
</script>
