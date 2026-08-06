<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        route.name === item.name
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
  { name: 'notifications.dashboard', label: 'Dashboard', to: { name: 'notifications.dashboard' } },
  { name: 'notifications.center', label: 'Center', to: { name: 'notifications.center' } },
  { name: 'notifications.unread', label: 'Unread', to: { name: 'notifications.unread' } },
  { name: 'notifications.history', label: 'History', to: { name: 'notifications.history' } },
  { name: 'notifications.preferences', label: 'Preferences', to: { name: 'notifications.preferences' } },
  { name: 'notifications.templates', label: 'Templates', to: { name: 'notifications.templates' } },
  { name: 'notifications.templates.approvals', label: 'Approvals', to: { name: 'notifications.templates.approvals' } },
  { name: 'notifications.logs', label: 'Logs', to: { name: 'notifications.logs' } },
];
</script>
