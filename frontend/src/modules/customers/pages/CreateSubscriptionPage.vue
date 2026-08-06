<template>
  <div>
    <PageHeader title="Create subscription" :description="`Add a plan for ${customerName}.`" />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <SubscriptionForm
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Create subscription"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.subscriptions', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SubscriptionForm from '@/modules/customers/components/SubscriptionForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useSubscriptionsStore } from '@/modules/customers/stores/subscriptions';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const store = useSubscriptionsStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

function sanitize(payload) {
  const next = { ...payload, customer_id: route.params.id };
  [
    'plan_name',
    'status',
    'payment_status',
    'amount',
    'starts_at',
    'expires_at',
    'renews_at',
    'notes',
  ].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.starts_at) next.starts_at = new Date(next.starts_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  if (next.renews_at) next.renews_at = new Date(next.renews_at).toISOString();
  if (next.amount !== null && next.amount !== undefined && next.amount !== '') {
    next.amount = Number(next.amount);
  }
  next.renewal_reminder_days = Number(next.renewal_reminder_days || 14);
  return next;
}

async function onSubmit(payload) {
  const subscription = await store.createSubscription(sanitize(payload));
  await router.push({
    name: 'customers.subscriptions.show',
    params: { id: route.params.id, subscriptionId: subscription.uuid },
  });
}
</script>
