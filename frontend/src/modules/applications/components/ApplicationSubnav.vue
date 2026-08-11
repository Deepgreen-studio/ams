<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex flex-wrap gap-x-1 gap-y-0" aria-label="Application sections">
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
  applicationId: { type: String, required: true },
});

const route = useRoute();

const items = computed(() => [
  {
    name: 'applications.show',
    label: 'Overview',
    match: ['applications.show'],
    to: { name: 'applications.show', params: { id: props.applicationId } },
  },
  {
    name: 'applications.versions',
    label: 'Versions',
    match: [
      'applications.versions',
      'applications.versions.create',
      'applications.versions.edit',
    ],
    to: { name: 'applications.versions', params: { id: props.applicationId } },
  },
  {
    name: 'applications.environments',
    label: 'Environments',
    match: [
      'applications.environments',
      'applications.environments.create',
      'applications.environments.show',
      'applications.environments.edit',
    ],
    to: { name: 'applications.environments', params: { id: props.applicationId } },
  },
  {
    name: 'applications.configurations',
    label: 'Configurations',
    match: [
      'applications.configurations',
      'applications.configurations.create',
      'applications.configurations.edit',
      'applications.configurations.flags',
      'applications.configurations.history',
    ],
    to: { name: 'applications.configurations', params: { id: props.applicationId } },
  },
  {
    name: 'applications.releases',
    label: 'Releases',
    match: [
      'applications.releases',
      'applications.releases.create',
      'applications.releases.show',
      'applications.releases.approval',
      'applications.releases.calendar',
      'applications.releases.timeline',
    ],
    to: { name: 'applications.releases', params: { id: props.applicationId } },
  },
  {
    name: 'applications.monitoring.crashes',
    label: 'Monitoring',
    match: [
      'applications.monitoring.crashes',
      'applications.monitoring.crash',
      'applications.monitoring.health',
      'applications.monitoring.charts',
      'applications.monitoring.alerts',
      'applications.monitoring.devices',
    ],
    to: { name: 'applications.monitoring.crashes', params: { id: props.applicationId } },
  },
  {
    name: 'applications.analytics',
    label: 'Analytics',
    match: [
      'applications.analytics',
      'applications.analytics.countries',
      'applications.analytics.devices',
      'applications.analytics.heatmap',
      'applications.analytics.trends',
    ],
    to: { name: 'applications.analytics', params: { id: props.applicationId } },
  },
  {
    name: 'applications.versions.compare',
    label: 'Compare',
    match: ['applications.versions.compare'],
    to: { name: 'applications.versions.compare', params: { id: props.applicationId } },
  },
  {
    name: 'applications.versions.timeline',
    label: 'Timeline',
    match: ['applications.versions.timeline'],
    to: { name: 'applications.versions.timeline', params: { id: props.applicationId } },
  },
  {
    name: 'applications.versions.history',
    label: 'History',
    match: ['applications.versions.history'],
    to: { name: 'applications.versions.history', params: { id: props.applicationId } },
  },
]);

function isActive(item) {
  return item.match.includes(route.name);
}
</script>
