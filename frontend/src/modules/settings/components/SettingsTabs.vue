<template>
  <div>
    <div class="mb-6 border-b border-zinc-200">
      <nav
        class="-mb-px flex gap-x-0.5 overflow-x-auto"
        aria-label="Settings sections"
      >
        <RouterLink
          v-for="tab in visibleTabs"
          :key="tab.to"
          :to="{ name: tab.to }"
          class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
          :class="
            isActive(tab.to)
              ? 'border-brand-600 text-brand-700'
              : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'
          "
        >
          {{ tab.label }}
        </RouterLink>
      </nav>
    </div>
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';

const route = useRoute();
const { can } = usePermissions();

const tabs = [
  { label: 'General', to: 'settings.general', permission: 'settings.view' },
  { label: 'Email', to: 'settings.email', permission: 'settings.email' },
  { label: 'Storage', to: 'settings.storage', permission: 'settings.storage' },
  { label: 'Security', to: 'settings.security', permission: 'settings.security' },
  { label: 'API', to: 'settings.api', permission: 'settings.api' },
  { label: 'Queue', to: 'settings.queue', permission: 'settings.queue' },
  { label: 'Media', to: 'settings.media', permission: 'settings.media' },
  { label: 'Files', to: 'settings.files', permission: 'settings.files' },
];

const visibleTabs = computed(() => tabs.filter((tab) => can(tab.permission)));

function isActive(name) {
  return route.name === name;
}
</script>
