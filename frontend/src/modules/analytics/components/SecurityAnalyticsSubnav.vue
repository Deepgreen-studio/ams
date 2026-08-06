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
  { name: 'analytics.security', label: 'Security', to: { name: 'analytics.security' }, match: ['analytics.security'] },
  { name: 'analytics.security.audit', label: 'Audit', to: { name: 'analytics.security.audit' }, match: ['analytics.security.audit'] },
  { name: 'analytics.security.timeline', label: 'Threat Timeline', to: { name: 'analytics.security.timeline' }, match: ['analytics.security.timeline'] },
  { name: 'analytics.security.risk', label: 'Risk', to: { name: 'analytics.security.risk' }, match: ['analytics.security.risk'] },
  { name: 'analytics.security.heatmap', label: 'Heatmap', to: { name: 'analytics.security.heatmap' }, match: ['analytics.security.heatmap'] },
  { name: 'analytics.security.export', label: 'Export', to: { name: 'analytics.security.export' }, match: ['analytics.security.export'] },
];
</script>
