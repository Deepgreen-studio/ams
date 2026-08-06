<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        item.match.includes(route.name)
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
  { name: 'ai.dashboard', label: 'Dashboard', to: { name: 'ai.dashboard' }, match: ['ai.dashboard'] },
  { name: 'ai.settings', label: 'Settings', to: { name: 'ai.settings' }, match: ['ai.settings'] },
  { name: 'ai.prompts', label: 'Prompt Manager', to: { name: 'ai.prompts' }, match: ['ai.prompts'] },
  { name: 'ai.conversations', label: 'Conversations', to: { name: 'ai.conversations' }, match: ['ai.conversations'] },
  { name: 'ai.analytics', label: 'Usage Analytics', to: { name: 'ai.analytics' }, match: ['ai.analytics'] },
  { name: 'ai.logs', label: 'AI Logs', to: { name: 'ai.logs' }, match: ['ai.logs'] },
];
</script>
