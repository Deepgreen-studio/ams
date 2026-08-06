<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!licenses.length"
      title="No licenses"
      description="Issue a license for this customer."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">License key</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Plan
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Expires
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in licenses" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-mono text-sm font-medium text-slate-900">{{ item.license_key }}</p>
              <p class="text-xs text-slate-500">
                {{ item.activation_count }}/{{ item.max_activations }} activations
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.subscription?.plan_name || '—' }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ formatDate(item.expires_at) }}
            </td>
            <td class="px-4 py-3">
              <LicenseStatusBadge :status="item.status" />
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{
                    name: 'customers.licenses.show',
                    params: { id: customerId, licenseId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  v-if="!item.deleted_at"
                  :to="{
                    name: 'customers.licenses.edit',
                    params: { id: customerId, licenseId: item.uuid },
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
import LicenseStatusBadge from '@/modules/customers/components/LicenseStatusBadge.vue';

defineProps({
  licenses: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  customerId: { type: String, required: true },
});

defineEmits(['archive']);

function formatDate(value) {
  if (!value) return 'Lifetime';
  return new Date(value).toLocaleDateString();
}
</script>
