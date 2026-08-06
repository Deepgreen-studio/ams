<template>
  <div>
    <PageHeader
      title="Version history"
      description="Complete version history including soft-deleted entries."
    />
    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="versionsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ versionsStore.error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="versionsStore.loading" class="space-y-3 p-6">
        <div v-for="n in 6" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!versionsStore.history.length"
        title="No history"
        description="Version activity will appear here as versions are created and updated."
      />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
                Updated
              </th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
                Updated by
              </th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">State</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in versionsStore.history" :key="item.uuid" class="hover:bg-slate-50/80">
              <td class="px-4 py-3">
                <p class="font-medium text-slate-900">{{ item.version_number }}</p>
                <p class="text-xs text-slate-500">Build {{ item.build_number || '—' }}</p>
              </td>
              <td class="px-4 py-3"><VersionStatusBadge :status="item.status" /></td>
              <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
                {{ formatDate(item.updated_at || item.created_at) }}
              </td>
              <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
                {{ item.updater?.full_name || item.creator?.full_name || '—' }}
              </td>
              <td class="px-4 py-3">
                <span v-if="item.deleted_at" class="text-xs font-medium text-rose-700"
                  >Deleted</span
                >
                <span v-else class="text-xs font-medium text-emerald-700">Active</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
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
  versionsStore.fetchHistory(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
