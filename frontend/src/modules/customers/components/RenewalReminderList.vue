<template>
  <div v-if="reminders.length" class="rounded-[12px] bg-amber-50 px-4 py-4 ring-1 ring-amber-100">
    <h3 class="text-sm font-semibold text-amber-900">Renewal reminders</h3>
    <p class="mt-1 text-xs text-amber-800">
      Subscriptions renewing or expiring within the reminder window.
    </p>
    <ul class="mt-3 divide-y divide-amber-100/80">
      <li
        v-for="item in reminders"
        :key="item.uuid"
        class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm"
      >
        <div>
          <p class="font-medium text-slate-900">{{ item.plan_name }}</p>
          <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <span>{{ formatDate(item.renews_at || item.expires_at) }}</span>
            <PaymentStatusBadge :status="item.payment_status" />
          </p>
        </div>
        <RouterLink
          :to="{
            name: 'customers.subscriptions.show',
            params: { id: customerId, subscriptionId: item.uuid },
          }"
          class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
        >
          Review
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import PaymentStatusBadge from '@/modules/customers/components/PaymentStatusBadge.vue';

defineProps({
  reminders: { type: Array, default: () => [] },
  customerId: { type: String, required: true },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString();
}
</script>
