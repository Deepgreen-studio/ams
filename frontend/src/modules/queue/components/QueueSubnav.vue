<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Queue sections">
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
  { name: 'queue.dashboard', label: 'Dashboard', to: { name: 'queue.dashboard' }, permission: 'queue.view' },
  { name: 'queue.running', label: 'Running', to: { name: 'queue.running' }, permission: 'queue.running' },
  { name: 'queue.failed', label: 'Failed', to: { name: 'queue.failed' }, permission: 'queue.failed' },
  { name: 'queue.statistics', label: 'Statistics', to: { name: 'queue.statistics' }, permission: 'queue.statistics' },
];

const visibleItems = computed(() => items.filter((item) => can(item.permission)));

function isActive(item) {
  return route.name === item.name;
}
</script>
