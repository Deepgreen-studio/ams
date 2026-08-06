<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!cases.length"
      title="No compliance cases found"
      description="Create a compliance case to begin governance tracking."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Case</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Type</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Priority</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Due</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in cases" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">
                {{ item.case_number }} · {{ item.company?.company_name || '—' }}
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.case_type_label || item.case_type || '—' }}
            </td>
            <td class="px-4 py-3">
              <CasePriorityBadge :priority="item.priority" :label="item.priority_label" />
            </td>
            <td class="px-4 py-3">
              <CaseStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.due_date || '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'compliance.cases.show', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'compliance.cases.edit', params: { id: item.uuid } }"
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
import CasePriorityBadge from '@/modules/compliance/components/CasePriorityBadge.vue';
import CaseStatusBadge from '@/modules/compliance/components/CaseStatusBadge.vue';

defineProps({
  cases: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['delete']);
</script>
