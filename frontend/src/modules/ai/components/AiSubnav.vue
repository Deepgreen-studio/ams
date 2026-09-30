<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="AI Assistant sections"
    >
      <RouterLink
        v-for="item in visibleItems"
        :key="item.name"
        :to="item.to"
        class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
        :class="
          isActive(item)
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
  { name: 'ai.dashboard', label: 'Dashboard', to: { name: 'ai.dashboard' }, match: ['ai.dashboard'], permission: 'ai.view' },
  { name: 'ai.settings', label: 'Settings', to: { name: 'ai.settings' }, match: ['ai.settings'], permission: 'ai.settings' },
  { name: 'ai.prompts', label: 'Prompt Manager', to: { name: 'ai.prompts' }, match: ['ai.prompts'], permission: 'ai.prompts' },
  { name: 'ai.conversations', label: 'Conversations', to: { name: 'ai.conversations' }, match: ['ai.conversations'], permission: ['ai.conversations', 'ai.chat'] },
  { name: 'ai.analytics', label: 'Usage Analytics', to: { name: 'ai.analytics' }, match: ['ai.analytics'], permission: 'ai.analytics' },
  { name: 'ai.logs', label: 'AI Logs', to: { name: 'ai.logs' }, match: ['ai.logs'], permission: 'ai.logs' },
];

const visibleItems = computed(() => items.filter((item) => can(item.permission)));

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
