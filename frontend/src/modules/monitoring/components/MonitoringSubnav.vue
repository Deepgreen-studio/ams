<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Monitoring sections"
    >
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="item.to"
        class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
        :class="
          isActive(item.name)
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

function isActive(name) {
  return route.name === name;
}
</script>
