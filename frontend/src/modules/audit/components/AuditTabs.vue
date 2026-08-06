<template>
  <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
    <RouterLink
      v-for="tab in tabs"
      :key="tab.to"
      :to="{ name: tab.to }"
      class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
      :class="route.name === tab.to ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'"
    >
      {{ tab.label }}
    </RouterLink>
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
