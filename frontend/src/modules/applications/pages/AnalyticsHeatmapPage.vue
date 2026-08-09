<template>
  <div>
    <!-- <PageHeader title="Activity heatmap" description="Session activity intensity by day of week and hour.">
      <template #actions>
        <RouterLink :to="{ name: 'applications.analytics', params: { id: route.params.id } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Dashboard</RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink :to="{ name: 'applications.analytics', params: { id: route.params.id } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Dashboard</RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="analyticsStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ analyticsStore.error }}</div>

    <AnalyticsHeatmap
      :days="analyticsStore.heatmap?.days || []"
      :hours="analyticsStore.heatmap?.hours || []"
      :matrix="analyticsStore.heatmap?.matrix || []"
      :max="analyticsStore.heatmap?.max || 0"
    />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import AnalyticsHeatmap from '@/modules/applications/components/AnalyticsHeatmap.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';

const route = useRoute();
const analyticsStore = useAnalyticsStore();

onMounted(() => analyticsStore.fetchHeatmap(route.params.id));
</script>
