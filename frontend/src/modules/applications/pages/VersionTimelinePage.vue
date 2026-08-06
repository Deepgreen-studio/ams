<template>
  <div>
    <PageHeader
      title="Version timeline"
      description="Semantic version progression for this application."
    />
    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="versionsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ versionsStore.error }}
    </div>

    <div v-if="versionsStore.loading" class="space-y-4">
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!versionsStore.timeline.length"
      title="No timeline entries"
      description="Create versions to build a release timeline."
    />

    <ol v-else class="relative space-y-6 border-l border-slate-200 pl-6">
      <li v-for="item in versionsStore.timeline" :key="item.uuid" class="relative">
        <span
          class="absolute -left-[1.95rem] mt-1.5 h-3.5 w-3.5 rounded-full border-2 border-white ring-2"
          :class="dotClass(item.status)"
        />
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="text-lg font-semibold text-slate-900">{{ item.version_number }}</h3>
              <p class="mt-1 text-sm text-slate-500">
                Build {{ item.build_number || '—' }}
                <span v-if="item.release_date"> · {{ formatDate(item.release_date) }}</span>
              </p>
            </div>
            <VersionStatusBadge :status="item.status" />
          </div>
          <p class="mt-3 text-sm text-slate-600">
            Min supported: {{ item.minimum_supported_version || '—' }}
          </p>
          <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">
            {{ item.release_notes || 'No release notes.' }}
          </p>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionStatusBadge from '@/modules/applications/components/VersionStatusBadge.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const versionsStore = useVersionsStore();

onMounted(() => {
  versionsStore.fetchTimeline(route.params.id);
});

function formatDate(value) {
  return new Date(value).toLocaleString();
}

function dotClass(status) {
  switch (status) {
    case 'production':
      return 'bg-emerald-500 ring-emerald-200';
    case 'beta':
      return 'bg-sky-500 ring-sky-200';
    case 'testing':
      return 'bg-amber-500 ring-amber-200';
    case 'rollback':
      return 'bg-rose-500 ring-rose-200';
    case 'deprecated':
      return 'bg-slate-400 ring-slate-200';
    default:
      return 'bg-violet-500 ring-violet-200';
  }
}
</script>
