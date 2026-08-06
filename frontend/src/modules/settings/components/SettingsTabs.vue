<template>
  <div class="space-y-6">
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <RouterLink
        v-for="tab in tabs"
        :key="tab.to"
        :to="{ name: tab.to }"
        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
        :class="isActive(tab.to) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'"
      >
        {{ tab.label }}
      </RouterLink>
    </div>
    <slot />
  </div>
</template>

<script setup>
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const tabs = [
  { label: 'General', to: 'settings.general' },
  { label: 'Email', to: 'settings.email' },
  { label: 'Storage', to: 'settings.storage' },
  { label: 'Security', to: 'settings.security' },
  { label: 'API', to: 'settings.api' },
  { label: 'Queue', to: 'settings.queue' },
  { label: 'Media', to: 'settings.media' },
  { label: 'Files', to: 'settings.files' },
];

function isActive(name) {
  return route.name === name;
}
</script>
