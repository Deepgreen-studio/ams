<template>
  <nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
      active-class="bg-brand-50 text-brand-700 hover:bg-brand-50"
    >
      {{ item.label }}
    </RouterLink>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  integrationId: { type: String, required: true },
});

const items = computed(() => [
  { name: 'integrations.show', label: 'Overview', to: { name: 'integrations.show', params: { id: props.integrationId } } },
  { name: 'integrations.configuration', label: 'API Configuration', to: { name: 'integrations.configuration', params: { id: props.integrationId } } },
  { name: 'integrations.connection', label: 'Connection Test', to: { name: 'integrations.connection', params: { id: props.integrationId } } },
  { name: 'integrations.tester', label: 'Request Tester', to: { name: 'integrations.tester', params: { id: props.integrationId } } },
  { name: 'integrations.history', label: 'History', to: { name: 'integrations.history', params: { id: props.integrationId } } },
]);
</script>
