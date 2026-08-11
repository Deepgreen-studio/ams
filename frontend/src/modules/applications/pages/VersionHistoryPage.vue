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

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
        <h3 class="text-base font-semibold text-slate-900">Version history</h3>
        <p class="text-xs text-slate-500">{{ versionsStore.history.length || 0 }} entries</p>
      </div>

      <div v-if="versionsStore.loading" class="space-y-3 px-6 py-5">
        <div v-for="n in 6" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
      </div>

      <EmptyState
        v-else-if="!versionsStore.history.length"
        title="No history"
        description="Version activity will appear here as versions are created and updated."
        class="px-6 py-10"
      />

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Version</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Updated
              </th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Updated by
              </th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">State</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in versionsStore.history"
              :key="item.uuid"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">{{ item.version_number }}</p>
                <p class="text-xs text-slate-500">Build {{ item.build_number || '—' }}</p>
              </td>
              <td class="px-5 py-4">
                <VersionStatusBadge :status="item.status" />
              </td>
              <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
                {{ formatDate(item.updated_at || item.created_at) }}
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
                {{ item.updater?.full_name || item.creator?.full_name || '—' }}
              </td>
              <td class="px-5 py-4">
                <span
                  v-if="item.deleted_at"
                  class="inline-flex rounded-[12px] bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700"
                >
                  Deleted
                </span>
                <span
                  v-else
                  class="inline-flex rounded-[12px] bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"
                >
                  Active
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
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
    if (message) toast.error(message, 'Unable to load version history');
  },
);

onMounted(() => {
  versionsStore.fetchHistory(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
