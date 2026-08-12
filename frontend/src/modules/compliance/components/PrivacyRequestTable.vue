<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!requests.length"
      title="No privacy requests found"
      description="Create a GDPR or privacy request to begin the workflow."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Request</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Type</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Identity</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Due</th>
            <th
              v-if="hasAnyAction"
              class="px-4 py-3 text-right font-semibold text-slate-600"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in requests" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.requester_name }}</p>
              <p class="text-xs text-slate-500">
                {{ item.request_number }} · {{ item.requester_email }}
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.request_type_label || item.request_type }}
            </td>
            <td class="px-4 py-3">
              <PrivacyStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.identity_verification_status_label || item.identity_verification_status }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">{{ item.due_date || '—' }}</td>
            <td v-if="hasAnyAction" class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  v-if="can('compliance.view')"
                  :to="{ name: 'compliance.privacy.show', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  v-if="can('compliance.update')"
                  :to="{ name: 'compliance.privacy.verify', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Verify
                </RouterLink>
                <button
                  v-if="can('compliance.delete')"
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
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import PrivacyStatusBadge from '@/modules/compliance/components/PrivacyStatusBadge.vue';

defineProps({
  requests: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['delete']);

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() =>
  canAny('compliance.view', 'compliance.update', 'compliance.delete'),
);
</script>
