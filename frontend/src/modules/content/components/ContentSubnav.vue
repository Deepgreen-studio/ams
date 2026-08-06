<template>
  <nav class="mb-5 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1.5">
    <div class="flex min-w-max gap-1">
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="{ name: item.name }"
        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition"
        :class="
          isActive(item)
            ? 'bg-brand-50 text-brand-700'
            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
        "
      >
        {{ item.label }}
      </RouterLink>
    </div>
  </nav>
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
  { name: 'content.categories.tree', label: 'Category Tree', match: ['content.categories.tree'] },
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
