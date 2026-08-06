<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!consents.length"
      title="No consents found"
      description="Record consent preferences to begin tracking."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Subject</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Type</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Version</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Source</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in consents" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.subject_name || '—' }}</p>
              <p class="text-xs text-slate-500">{{ item.subject_email || '—' }}</p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.consent_type?.name || '—' }}
            </td>
            <td class="px-4 py-3">
              <ConsentStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">{{ item.consent_version }}</td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.source_label || item.source }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'compliance.consents.show', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <button
                  v-if="item.granted"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('withdraw', item)"
                >
                  Withdraw
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
import ConsentStatusBadge from '@/modules/compliance/components/ConsentStatusBadge.vue';

defineProps({
  consents: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['withdraw']);
</script>
