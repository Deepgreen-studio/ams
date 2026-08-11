<template>
  <div>
    <div class="mb-6 border-b border-zinc-200">
      <nav
        class="-mb-px flex gap-x-0.5 overflow-x-auto"
        aria-label="Settings sections"
      >
        <RouterLink
          v-for="tab in tabs"
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
