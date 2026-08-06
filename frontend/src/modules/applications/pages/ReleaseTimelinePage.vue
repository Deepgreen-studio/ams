<template>
  <div>
    <PageHeader
      title="Release timeline"
      description="Chronological history of release planning and deployments."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.releases', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="releasesStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ releasesStore.error }}
    </div>

    <div v-if="releasesStore.loading" class="space-y-3">
      <div v-for="n in 5" :key="n" class="h-16 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!releasesStore.timelineReleases.length"
      title="No timeline events"
      description="Release activity will appear here as plans are created."
    />

    <ol v-else class="relative space-y-4 border-l border-slate-200 pl-6">
      <li v-for="item in releasesStore.timelineReleases" :key="item.uuid" class="relative">
        <span
          class="absolute -left-[1.9rem] mt-1.5 h-3 w-3 rounded-full bg-brand-600 ring-4 ring-white"
        />
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <h3 class="font-semibold text-slate-900">{{ item.name }}</h3>
              <p class="mt-1 text-sm text-slate-500">
                {{ item.version_label }} · {{ item.status_label || item.status }} ·
                {{ item.approval_status_label || item.approval_status }}
              </p>
            </div>
            <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
          </div>
          <p v-if="item.plan_summary" class="mt-2 text-sm text-slate-600">
            {{ item.plan_summary }}
          </p>
          <RouterLink
            :to="{
              name: 'applications.releases.show',
              params: { id: route.params.id, releaseId: item.uuid },
            }"
            class="mt-3 inline-block text-sm font-medium text-brand-700 hover:underline"
          >
            View release
          </RouterLink>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';

const route = useRoute();
const releasesStore = useReleasesStore();

onMounted(() => {
  releasesStore.fetchTimeline(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
