<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!versions.length"
      title="No versions yet"
      description="Create the first semantic version for this application."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Build
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Release date
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 xl:table-cell">
              Min supported
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in versions" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.version_number }}</p>
              <p class="text-xs text-slate-500">
                {{ item.major }}.{{ item.minor }}.{{ item.patch }}
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.build_number || '—' }}
            </td>
            <td class="px-4 py-3"><VersionStatusBadge :status="item.status" /></td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ formatDate(item.release_date) }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 xl:table-cell">
              {{ item.minimum_supported_version || '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{
                    name: 'applications.versions.edit',
                    params: { id: applicationId, versionId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('delete', item)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import VersionStatusBadge from '@/modules/applications/components/VersionStatusBadge.vue';

defineProps({
  applicationId: { type: String, required: true },
  versions: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString();
}
</script>
