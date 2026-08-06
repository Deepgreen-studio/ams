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
  { name: 'monitoring.dashboard', label: 'Health Dashboard', to: { name: 'monitoring.dashboard' } },
  { name: 'monitoring.realtime', label: 'Real-Time', to: { name: 'monitoring.realtime' } },
  { name: 'monitoring.api', label: 'API Monitor', to: { name: 'monitoring.api' } },
  { name: 'monitoring.webhooks', label: 'Webhook Monitor', to: { name: 'monitoring.webhooks' } },
  { name: 'monitoring.queue', label: 'Queue Monitor', to: { name: 'monitoring.queue' } },
  { name: 'monitoring.integrations', label: 'Integrations', to: { name: 'monitoring.integrations' } },
  { name: 'monitoring.timeline', label: 'Incident Timeline', to: { name: 'monitoring.timeline' } },
  { name: 'monitoring.history', label: 'Response History', to: { name: 'monitoring.history' } },
  { name: 'monitoring.alerts', label: 'Alerts', to: { name: 'monitoring.alerts' } },
];
</script>
