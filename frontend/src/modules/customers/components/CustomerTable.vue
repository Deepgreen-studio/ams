<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!customers.length"
      title="No customers found"
      description="Create your first customer to get started."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Type
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Company
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 xl:table-cell">
              Country
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="customer in customers" :key="customer.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ customer.display_name }}</p>
              <p class="text-xs text-slate-500">{{ customer.email }}</p>
            </td>
            <td class="hidden px-4 py-3 md:table-cell">
              <TypeBadge :type="customer.customer_type" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ customer.company?.company_name || '—' }}
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="customer.status" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 xl:table-cell">
              {{ customer.country || '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'customers.show', params: { id: customer.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'customers.edit', params: { id: customer.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('archive', customer)"
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
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import TypeBadge from '@/modules/customers/components/TypeBadge.vue';

defineProps({
  customers: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['archive']);
</script>
