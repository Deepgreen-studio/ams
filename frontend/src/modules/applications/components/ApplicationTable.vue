<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!applications.length"
      title="No applications found"
      description="Register a customer application to manage it from one dashboard."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Application</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Platform
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Category
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Version
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in applications" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  <img
                    v-if="item.icon"
                    :src="item.icon"
                    alt=""
                    class="h-full w-full object-cover"
                  />
                  <span v-else>{{ initials(item.name) }}</span>
                </div>
                <div>
                  <p class="font-medium text-slate-900">{{ item.name }}</p>
                  <p class="text-xs text-slate-500">
                    {{ item.slug }} · {{ item.company?.company_name || '—' }}
                  </p>
                </div>
              </div>
            </td>
            <td class="hidden px-4 py-3 md:table-cell">
              <StatusBadge :status="item.platform" kind="platform" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.category_label || item.category || '—' }}
            </td>
            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.current_version || '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'applications.show', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  >View</RouterLink
                >
                <RouterLink
                  :to="{ name: 'applications.edit', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  >Edit</RouterLink
                >
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
import StatusBadge from '@/modules/applications/components/StatusBadge.vue';

defineProps({
  applications: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

function initials(name) {
  return (name || 'A').slice(0, 2).toUpperCase();
}
</script>
