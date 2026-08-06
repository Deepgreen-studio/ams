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
  { name: 'analytics.business', label: 'Business', to: { name: 'analytics.business' }, match: ['analytics.business'] },
  { name: 'analytics.business.customers', label: 'Customers', to: { name: 'analytics.business.customers' }, match: ['analytics.business.customers'] },
  { name: 'analytics.business.revenue', label: 'Revenue', to: { name: 'analytics.business.revenue' }, match: ['analytics.business.revenue'] },
  { name: 'analytics.business.applications', label: 'Applications', to: { name: 'analytics.business.applications' }, match: ['analytics.business.applications'] },
  { name: 'analytics.business.growth', label: 'Growth & Forecast', to: { name: 'analytics.business.growth' }, match: ['analytics.business.growth'] },
];
</script>
