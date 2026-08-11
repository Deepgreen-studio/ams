<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex flex-wrap gap-x-1 gap-y-0" aria-label="Integration sections">
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
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const props = defineProps({
  integrationId: { type: String, required: true },
});

const route = useRoute();

const items = computed(() => [
  {
    name: 'integrations.show',
    label: 'Overview',
    match: ['integrations.show'],
    to: { name: 'integrations.show', params: { id: props.integrationId } },
  },
  {
    name: 'integrations.configuration',
    label: 'API Configuration',
    match: ['integrations.configuration'],
    to: { name: 'integrations.configuration', params: { id: props.integrationId } },
  },
  {
    name: 'integrations.connection',
    label: 'Connection Test',
    match: ['integrations.connection'],
    to: { name: 'integrations.connection', params: { id: props.integrationId } },
  },
  {
    name: 'integrations.tester',
    label: 'Request Tester',
    match: ['integrations.tester'],
    to: { name: 'integrations.tester', params: { id: props.integrationId } },
  },
  {
    name: 'integrations.history',
    label: 'History',
    match: ['integrations.history'],
    to: { name: 'integrations.history', params: { id: props.integrationId } },
  },
]);

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
