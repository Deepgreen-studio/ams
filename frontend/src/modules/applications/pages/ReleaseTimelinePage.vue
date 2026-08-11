<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'applications.releases', params: { id: route.params.id } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Dashboard
      </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="releasesStore.loading" class="space-y-3">
      <div v-for="n in 5" :key="n" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div
      v-else-if="!releasesStore.timelineReleases.length"
      class="rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="No timeline events"
        description="Release activity will appear here as plans are created."
        class="px-8 py-6"
      />
    </div>

    <ol v-else class="relative space-y-4 border-l border-zinc-200 pl-6">
      <li
        v-for="item in releasesStore.timelineReleases"
        :key="item.uuid"
        class="relative"
      >
        <span
          class="absolute -left-[1.9rem] top-1/2 h-3 w-3 -translate-y-1/2 rounded-full bg-brand-600 ring-4 ring-[#faf9f7]"
          aria-hidden="true"
        />
        <article
          class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200 sm:p-6"
        >
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate text-base font-semibold text-slate-900">{{ item.name }}</h3>
                <ReleaseStatusBadge :status="item.status" :label="item.status_label" />
                <span
                  class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
                  :class="approvalClasses(item.approval_status)"
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="approvalDot(item.approval_status)"
                  />
                  {{ item.approval_status_label || formatLabel(item.approval_status) }}
                </span>
              </div>
              <p class="mt-1.5 text-sm text-slate-500">
                {{ item.version_label || '—' }}
                <span v-if="item.release_type_label || item.release_type">
                  · {{ item.release_type_label || item.release_type }}
                </span>
                <span v-if="item.environment?.name"> · {{ item.environment.name }}</span>
              </p>
              <p v-if="item.plan_summary" class="mt-2 text-sm text-slate-600">
                {{ item.plan_summary }}
              </p>
              <RouterLink
                :to="{
                  name: 'applications.releases.show',
                  params: { id: route.params.id, releaseId: item.uuid },
                }"
                class="mt-3 inline-flex text-sm font-medium text-brand-700 transition hover:text-brand-800"
              >
                View release
              </RouterLink>
            </div>
            <p
              class="shrink-0 rounded-[12px] bg-zinc-50 px-3 py-2 text-xs font-medium text-slate-600"
            >
              {{ formatDate(item.created_at) }}
            </p>
          </div>
        </article>
      </li>
    </ol>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import ReleaseStatusBadge from '@/modules/applications/components/ReleaseStatusBadge.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const releasesStore = useReleasesStore();
const toast = useToast();

watch(
  () => releasesStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load timeline');
  },
);

onMounted(async () => {
  try {
    await releasesStore.fetchTimeline(route.params.id);
  } catch {
    // Toast handled by watcher.
  }
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatLabel(value) {
  return String(value || 'pending')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function approvalClasses(status) {
  switch (status) {
    case 'approved':
      return 'border-emerald-600 text-emerald-700';
    case 'rejected':
      return 'border-rose-500 text-rose-700';
    case 'not_required':
      return 'border-slate-400 text-slate-600';
    case 'pending':
    default:
      return 'border-amber-500 text-amber-700';
  }
}

function approvalDot(status) {
  switch (status) {
    case 'approved':
      return 'bg-emerald-600';
    case 'rejected':
      return 'bg-rose-500';
    case 'not_required':
      return 'bg-slate-400';
    case 'pending':
    default:
      return 'bg-amber-500';
  }
}
</script>
