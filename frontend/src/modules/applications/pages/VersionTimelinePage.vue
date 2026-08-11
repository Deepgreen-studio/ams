<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'applications.versions', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          All versions
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create version
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="versionsStore.loading" class="space-y-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div
      v-else-if="!versionsStore.timeline.length"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="No timeline entries"
        description="Create versions to build a release timeline."
        class="px-6 py-10"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create version
          </RouterLink>
        </template>
      </EmptyState>
    </div>

    <div v-else class="relative">
      <div
        class="pointer-events-none absolute bottom-5 left-4 top-5 w-px -translate-x-1/2 bg-zinc-200"
        aria-hidden="true"
      />

      <ol class="space-y-5">
        <li
          v-for="item in versionsStore.timeline"
          :key="item.uuid"
          class="relative flex items-stretch gap-5"
        >
          <div class="relative z-10 flex w-8 shrink-0 items-center justify-center">
            <span
              class="h-3.5 w-3.5 rounded-full border-2 border-white bg-brand-600 ring-4 ring-brand-100"
            />
          </div>

          <article
            class="min-w-0 flex-1 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
          >
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 class="text-xl font-bold tracking-tight text-slate-900">
                  {{ item.version_number }}
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                  Build {{ item.build_number || '—' }}
                  <span v-if="item.release_date"> · {{ formatDate(item.release_date) }}</span>
                </p>
              </div>
              <VersionStatusBadge :status="item.status" />
            </div>

            <p class="mt-4 text-sm text-slate-600">
              Min supported: {{ item.minimum_supported_version || '—' }}
            </p>

            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">
              {{ item.release_notes || 'No release notes.' }}
            </p>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
              <RouterLink
                :to="{
                  name: 'applications.versions.edit',
                  params: { id: route.params.id, versionId: item.uuid },
                }"
                class="inline-flex items-center gap-1.5 rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50"
              >
                Edit
              </RouterLink>
              <RouterLink
                :to="{ name: 'applications.versions.compare', params: { id: route.params.id } }"
                class="inline-flex items-center gap-1.5 rounded-[12px] px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-zinc-50"
              >
                Compare
              </RouterLink>
            </div>
          </article>
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionStatusBadge from '@/modules/applications/components/VersionStatusBadge.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const versionsStore = useVersionsStore();
const toast = useToast();

watch(
  () => versionsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load version timeline');
  },
);

onMounted(() => {
  versionsStore.fetchTimeline(route.params.id);
});

function formatDate(value) {
  return new Date(value).toLocaleString();
}
</script>
