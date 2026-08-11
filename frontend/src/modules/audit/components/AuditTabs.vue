<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Audit sections"
    >
      <RouterLink
        v-for="tab in tabs"
        :key="tab.to"
        :to="{ name: tab.to }"
        class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
        :class="
          route.name === tab.to
            ? 'border-brand-600 text-brand-700'
            : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'
        "
      >
        {{ tab.label }}
      </RouterLink>
    </nav>
  </div>
</template>

<script setup>
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();
const tabs = [
  { label: 'Activity', to: 'audit.activity' },
  { label: 'Audit Trail', to: 'audit.trail' },
  { label: 'Login History', to: 'audit.login' },
  { label: 'System Events', to: 'audit.events' },
  { label: 'API Logs', to: 'audit.api' },
  { label: 'Errors', to: 'audit.errors' },
];
</script>
