<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Content sections"
    >
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="{ name: item.name }"
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
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  { name: 'content.dashboard', label: 'Dashboard', match: ['content.dashboard'] },
  {
    name: 'content.index',
    label: 'Content',
    match: ['content.index', 'content.create', 'content.edit', 'content.show'],
  },
  { name: 'content.workflow', label: 'Approval Queue', match: ['content.workflow'] },
  { name: 'content.media', label: 'Media Library', match: ['content.media'] },
  {
    name: 'content.categories',
    label: 'Categories',
    match: ['content.categories', 'content.categories.create', 'content.categories.edit'],
  },
  {
    name: 'content.tags',
    label: 'Tags',
    match: ['content.tags', 'content.tags.create', 'content.tags.edit'],
  },
  { name: 'content.delivery', label: 'Delivery Preview', match: ['content.delivery'] },
  { name: 'content.seo', label: 'SEO Tools', match: ['content.seo'] },
  { name: 'content.api-explorer', label: 'API Explorer', match: ['content.api-explorer'] },
];

function isActive(item) {
  const names = item.match || [item.name];
  return names.includes(route.name);
}
</script>
