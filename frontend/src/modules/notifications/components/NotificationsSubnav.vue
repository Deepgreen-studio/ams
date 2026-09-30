<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Notification sections"
    >
      <RouterLink
        v-for="item in visibleItems"
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
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';

const route = useRoute();
const { can } = usePermissions();

const items = [
  { name: 'notifications.dashboard', label: 'Dashboard', to: { name: 'notifications.dashboard' }, permission: 'notifications.view' },
  { name: 'notifications.center', label: 'Center', to: { name: 'notifications.center' }, permission: 'notifications.center' },
  { name: 'notifications.unread', label: 'Unread', to: { name: 'notifications.unread' }, permission: 'notifications.unread' },
  { name: 'notifications.history', label: 'History', to: { name: 'notifications.history' }, permission: 'notifications.history' },
  { name: 'notifications.preferences', label: 'Preferences', to: { name: 'notifications.preferences' }, permission: 'notifications.preferences' },
  { name: 'notifications.templates', label: 'Templates', to: { name: 'notifications.templates' }, permission: 'notifications.templates' },
  { name: 'notifications.templates.approvals', label: 'Approvals', to: { name: 'notifications.templates.approvals' }, permission: 'notifications.approve' },
  { name: 'notifications.logs', label: 'Logs', to: { name: 'notifications.logs' }, permission: 'notifications.logs' },
];

const visibleItems = computed(() => items.filter((item) => can(item.permission)));

function isActive(name) {
  if (route.name === name) return true;
  if (name === 'notifications.templates' && String(route.name || '').startsWith('notifications.templates') && route.name !== 'notifications.templates.approvals') {
    return true;
  }
  return false;
}
</script>
