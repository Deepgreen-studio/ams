<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav
      class="-mb-px flex gap-x-0.5 overflow-x-auto"
      aria-label="Webhook sections"
    >
      <RouterLink
        v-for="item in items"
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
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  {
    name: 'webhooks.index',
    label: 'Webhooks',
    to: { name: 'webhooks.index' },
    match: [
      'webhooks.index',
      'webhooks.create',
      'webhooks.show',
      'webhooks.edit',
      'webhooks.tester',
    ],
  },
  {
    name: 'webhooks.logs',
    label: 'Logs',
    to: { name: 'webhooks.logs' },
    match: ['webhooks.logs'],
  },
  {
    name: 'webhooks.events',
    label: 'Events',
    to: { name: 'webhooks.events' },
    match: ['webhooks.events'],
  },
];

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
