<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        item.match.includes(route.name)
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
  { name: 'analytics.dashboard', label: 'Overview', to: { name: 'analytics.dashboard' }, match: ['analytics.dashboard'] },
  { name: 'analytics.dashboards', label: 'Dashboards', to: { name: 'analytics.dashboards' }, match: ['analytics.dashboards', 'analytics.dashboards.show', 'analytics.dashboards.designer'] },
  { name: 'analytics.templates', label: 'Templates', to: { name: 'analytics.templates' }, match: ['analytics.templates'] },
  { name: 'analytics.reports', label: 'Reports', to: { name: 'analytics.reports' }, match: ['analytics.reports', 'analytics.reports.designer'] },
  { name: 'analytics.saved-reports', label: 'Saved Reports', to: { name: 'analytics.saved-reports' }, match: ['analytics.saved-reports'] },
  { name: 'analytics.saved-views', label: 'Saved Views', to: { name: 'analytics.saved-views' }, match: ['analytics.saved-views'] },
  { name: 'analytics.events', label: 'Events', to: { name: 'analytics.events' }, match: ['analytics.events'] },
  { name: 'analytics.business', label: 'Business', to: { name: 'analytics.business' }, match: ['analytics.business', 'analytics.business.customers', 'analytics.business.revenue', 'analytics.business.applications', 'analytics.business.growth'] },
  { name: 'analytics.executive', label: 'Executive', to: { name: 'analytics.executive' }, match: ['analytics.executive', 'analytics.executive.admin', 'analytics.executive.operations', 'analytics.executive.compliance', 'analytics.executive.support', 'analytics.executive.customer', 'analytics.executive.scorecards', 'analytics.executive.trends', 'analytics.executive.forecast'] },
  { name: 'analytics.security', label: 'Security', to: { name: 'analytics.security' }, match: ['analytics.security', 'analytics.security.audit', 'analytics.security.timeline', 'analytics.security.risk', 'analytics.security.heatmap', 'analytics.security.export'] },
  { name: 'analytics.operational', label: 'Operational', to: { name: 'analytics.operational' }, match: ['analytics.operational', 'analytics.delivery', 'analytics.automation', 'analytics.workflows', 'analytics.ai'] },
];
</script>
