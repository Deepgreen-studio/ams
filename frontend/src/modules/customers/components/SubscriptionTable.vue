<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!subscriptions.length"
      title="No subscriptions"
      description="Create a subscription plan for this customer."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Plan</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Renewal
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Payment
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in subscriptions" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.plan_name }}</p>
              <p class="text-xs capitalize text-slate-500">
                {{ item.plan_type?.replaceAll('_', '') }}
              </p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ formatDate(item.renews_at || item.expires_at) }}
              <span v-if="item.is_renewal_due_soon" class="ml-1 text-xs font-medium text-amber-700"
                >Due soon</span
              >
            </td>
            <td class="px-4 py-3">
              <SubscriptionStatusBadge :status="item.status" />
            </td>
            <td class="hidden px-4 py-3 lg:table-cell">
              <PaymentStatusBadge :status="item.payment_status" />
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{
                    name: 'customers.subscriptions.show',
                    params: { id: customerId, subscriptionId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{
                    name: 'customers.subscriptions.edit',
                    params: { id: customerId, subscriptionId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <button
                  v-if="!item.deleted_at && item.status !== 'cancelled'"
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
import PaymentStatusBadge from '@/modules/customers/components/PaymentStatusBadge.vue';
import SubscriptionStatusBadge from '@/modules/customers/components/SubscriptionStatusBadge.vue';

defineProps({
  subscriptions: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  customerId: { type: String, required: true },
});

defineEmits(['archive']);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString();
}
</script>
