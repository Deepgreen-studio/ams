<template>
  <div>
    <PageHeader title="Edit subscription" description="Update plan, dates, and payment status." />
    <div v-if="store.loading && !subscription" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <div v-else-if="subscription" class="rounded-xl border border-slate-200 bg-white p-6">
      <SubscriptionForm
        :initial="subscription"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="
          router.push({
            name: 'customers.subscriptions.show',
            params: { id: route.params.id, subscriptionId: route.params.subscriptionId },
          })
        "
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SubscriptionForm from '@/modules/customers/components/SubscriptionForm.vue';
import { useSubscriptionsStore } from '@/modules/customers/stores/subscriptions';

const route = useRoute();
const router = useRouter();
const store = useSubscriptionsStore();

const subscription = computed(() => store.currentSubscription);

onMounted(() => {
  store.fetchSubscription(route.params.subscriptionId);
});

function sanitize(payload) {
  const next = { ...payload };
  delete next.issue_license;
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
  await store.updateSubscription(route.params.subscriptionId, sanitize(payload));
  await router.push({
    name: 'customers.subscriptions.show',
    params: { id: route.params.id, subscriptionId: route.params.subscriptionId },
  });
}
</script>
