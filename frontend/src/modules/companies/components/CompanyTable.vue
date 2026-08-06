<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!companies.length"
      title="No companies found"
      description="Create your first organization to get started."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Company</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Country
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Org units
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="company in companies" :key="company.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ company.company_name }}</p>
              <p class="text-xs text-slate-500">
                {{ company.email || company.registration_number || '—' }}
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ company.country || '—' }}
            </td>
            <td class="px-4 py-3"><StatusBadge :status="company.status" /></td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ company.departments_count || 0 }} dept · {{ company.teams_count || 0 }} teams ·
              {{ company.locations_count || 0 }} locs
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'companies.show', params: { id: company.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  >View</RouterLink
                >
                <RouterLink
                  :to="{ name: 'companies.edit', params: { id: company.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  >Edit</RouterLink
                >
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('delete', company)"
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
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
  companies: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);
</script>
