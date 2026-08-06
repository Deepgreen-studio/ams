<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!assignments.length"
      title="No applications assigned"
      description="Assign an application to this customer."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Application</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Environment
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Ownership
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in assignments" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.application?.name || '—' }}</p>
              <p class="text-xs text-slate-500">
                {{ item.integration?.name || item.application?.platform || '—' }}
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.environment?.name || '—' }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ formatOwnership(item.ownership_type) }}
            </td>
            <td class="px-4 py-3">
              <AssignmentStatusBadge :status="item.status" />
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{
                    name: 'customers.applications.show',
                    params: { id: customerId, assignmentId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{
                    name: 'customers.applications.edit',
                    params: { id: customerId, assignmentId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <button
                  v-if="!item.deleted_at"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('archive', item)"
                >
                  Archive
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
import AssignmentStatusBadge from '@/modules/customers/components/AssignmentStatusBadge.vue';

defineProps({
  assignments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  customerId: { type: String, required: true },
});

defineEmits(['archive']);

function formatOwnership(value) {
  return (value || 'customer_owned').replaceAll('_', '').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>
