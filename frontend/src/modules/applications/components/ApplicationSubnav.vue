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
  applicationId: { type: String, required: true },
});

const items = computed(() => [
  { name: 'applications.show', label: 'Overview', to: { name: 'applications.show', params: { id: props.applicationId } } },
  { name: 'applications.versions', label: 'Versions', to: { name: 'applications.versions', params: { id: props.applicationId } } },
  { name: 'applications.environments', label: 'Environments', to: { name: 'applications.environments', params: { id: props.applicationId } } },
  { name: 'applications.configurations', label: 'Configurations', to: { name: 'applications.configurations', params: { id: props.applicationId } } },
  { name: 'applications.releases', label: 'Releases', to: { name: 'applications.releases', params: { id: props.applicationId } } },
  { name: 'applications.monitoring.crashes', label: 'Monitoring', to: { name: 'applications.monitoring.crashes', params: { id: props.applicationId } } },
  { name: 'applications.analytics', label: 'Analytics', to: { name: 'applications.analytics', params: { id: props.applicationId } } },
  { name: 'applications.versions.compare', label: 'Compare', to: { name: 'applications.versions.compare', params: { id: props.applicationId } } },
  { name: 'applications.versions.timeline', label: 'Timeline', to: { name: 'applications.versions.timeline', params: { id: props.applicationId } } },
  { name: 'applications.versions.history', label: 'History', to: { name: 'applications.versions.history', params: { id: props.applicationId } } },
]);
</script>
