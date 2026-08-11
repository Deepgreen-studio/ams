<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-for="link in navLinks"
          :key="link.name"
          :to="{ name: link.name, params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] px-5 py-2.5 text-sm font-medium transition"
          :class="
            isActive(link.name)
              ? 'bg-brand-600 text-white hover:bg-brand-700'
              : 'border border-zinc-200 text-slate-700 hover:bg-zinc-50'
          "
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.loading && !analyticsStore.heatmap"
      class="h-72 animate-pulse rounded-[12px] bg-slate-100"
    />

    <AnalyticsHeatmap
      v-else
      :days="analyticsStore.heatmap?.days || []"
      :hours="analyticsStore.heatmap?.hours || []"
      :matrix="analyticsStore.heatmap?.matrix || []"
      :max="analyticsStore.heatmap?.max || 0"
    />
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import AnalyticsHeatmap from '@/modules/applications/components/AnalyticsHeatmap.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const analyticsStore = useAnalyticsStore();
const toast = useToast();

const navLinks = [
  { name: 'applications.analytics', label: 'Dashboard' },
  { name: 'applications.analytics.trends', label: 'Trends' },
  { name: 'applications.analytics.heatmap', label: 'Heatmap' },
  { name: 'applications.analytics.countries', label: 'Countries' },
  { name: 'applications.analytics.devices', label: 'Devices' },
];

function isActive(name) {
  return route.name === name;
}

watch(
  () => analyticsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load activity heatmap');
  },
);

onMounted(() => analyticsStore.fetchHeatmap(route.params.id));
</script>
