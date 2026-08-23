<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex flex-wrap gap-x-1" aria-label="Integrations sections">
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="item.to"
        class="border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
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
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  {
    name: 'integrations.index',
    label: 'Registry',
    to: { name: 'integrations.index' },
    match: [
      'integrations.index',
      'integrations.create',
      'integrations.show',
      'integrations.edit',
      'integrations.configuration',
      'integrations.connection',
      'integrations.tester',
      'integrations.history',
    ],
  },
  {
    name: 'integrations.docs',
    label: 'API Docs',
    to: { name: 'integrations.docs' },
    match: ['integrations.docs'],
  },
];

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
