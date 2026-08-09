<template>
  <div>
    <!-- <PageHeader
      :title="subscription?.plan_name || 'Subscription details'"
      description="Plan, licensing, and payment status."
    >
      <template #actions>
        <template v-if="subscription">
          <RouterLink
            :to="{ name: 'customers.subscriptions', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back
          </RouterLink>
          <RouterLink
            :to="{
              name: 'customers.subscriptions.edit',
              params: { id: route.params.id, subscriptionId: subscription.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="subscription.status !== 'cancelled' && !subscription.deleted_at"
            type="button"
            class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50"
            :disabled="store.saving"
            @click="cancelSubscription"
          >
            Cancel plan
          </button>
          <button
            v-if="subscription.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showArchive = true"
          >
            Archive
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="subscription">
          <RouterLink
            :to="{ name: 'customers.subscriptions', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back
          </RouterLink>
          <RouterLink
            :to="{
              name: 'customers.subscriptions.edit',
              params: { id: route.params.id, subscriptionId: subscription.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="subscription.status !== 'cancelled' && !subscription.deleted_at"
            type="button"
            class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50"
            :disabled="store.saving"
            @click="cancelSubscription"
          >
            Cancel plan
          </button>
          <button
            v-if="subscription.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showArchive = true"
          >
            Archive
          </button>
    </Teleport>

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !subscription" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="subscription" class="space-y-6">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center gap-3">
          <SubscriptionStatusBadge :status="subscription.status" />
          <PaymentStatusBadge :status="subscription.payment_status" />
          <span class="text-xs uppercase tracking-wide text-slate-500">{{
            subscription.payment_provider
          }}</span>
        </div>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs text-slate-500">Plan type</dt>
            <dd class="text-sm capitalize text-slate-900">
              {{ subscription.plan_type?.replaceAll('_', '') }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Amount</dt>
            <dd class="text-sm text-slate-900">
              {{ subscription.currency }} {{ subscription.amount ?? '0.00' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Starts</dt>
            <dd class="text-sm text-slate-900">{{ formatDate(subscription.starts_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Expires</dt>
            <dd class="text-sm text-slate-900">{{ formatDate(subscription.expires_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Renews</dt>
            <dd class="text-sm text-slate-900">{{ formatDate(subscription.renews_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">External IDs</dt>
            <dd class="text-sm text-slate-900">
              {{ subscription.external_subscription_id || 'Not linked (Stripe-ready)' }}
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs text-slate-500">Features</dt>
            <dd class="text-sm text-slate-900">
              {{ (subscription.features || []).join(',') || '—' }}
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs text-slate-500">Notes</dt>
            <dd class="whitespace-pre-wrap text-sm text-slate-900">
              {{ subscription.notes || '—' }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Licenses</h3>
          <RouterLink
            :to="{
              name: 'customers.licenses.create',
              params: { id: route.params.id },
              query: { subscription: subscription.uuid },
            }"
            class="text-sm font-medium text-brand-700 hover:text-brand-800"
          >
            Issue license
          </RouterLink>
        </div>
        <ul v-if="subscription.licenses?.length" class="mt-4 divide-y divide-slate-100">
          <li
            v-for="license in subscription.licenses"
            :key="license.uuid"
            class="flex flex-wrap items-center justify-between gap-2 py-3"
          >
            <div>
              <p class="font-mono text-sm text-slate-900">{{ license.license_key }}</p>
              <LicenseStatusBadge :status="license.status" />
            </div>
            <RouterLink
              :to="{
                name: 'customers.licenses.show',
                params: { id: route.params.id, licenseId: license.uuid },
              }"
              class="text-sm font-medium text-brand-700"
            >
              View
            </RouterLink>
          </li>
        </ul>
        <p v-else class="mt-3 text-sm text-slate-500">No licenses issued yet.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Timeline</h3>
        <ul v-if="store.timeline.length" class="mt-4 space-y-3">
          <li
            v-for="(item, index) in store.timeline"
            :key="index"
            class="border-l-2 border-slate-200 pl-3 text-sm"
          >
            <p class="font-medium text-slate-900">
              {{ item.description || item.event || 'Activity' }}
            </p>
            <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
          </li>
        </ul>
        <p v-else class="mt-3 text-sm text-slate-500">No timeline entries yet.</p>
      </div>
    </div>

    <DeleteConfirmation
      :open="showArchive"
      title="Archive subscription"
      :message="`Archive ${subscription?.plan_name || 'this subscription'}?`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="showArchive = false"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import LicenseStatusBadge from '@/modules/customers/components/LicenseStatusBadge.vue';
import PaymentStatusBadge from '@/modules/customers/components/PaymentStatusBadge.vue';
import SubscriptionStatusBadge from '@/modules/customers/components/SubscriptionStatusBadge.vue';
import { useSubscriptionsStore } from '@/modules/customers/stores/subscriptions';

const route = useRoute();
const router = useRouter();
const store = useSubscriptionsStore();
const showArchive = ref(false);

const subscription = computed(() => store.currentSubscription);

onMounted(async () => {
  await store.fetchSubscription(route.params.subscriptionId);
  await store.fetchTimeline(route.params.subscriptionId);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function cancelSubscription() {
  const reason = window.prompt('Cancellation reason (optional)') || '';
  await store.cancelSubscription(route.params.subscriptionId, reason);
  await store.fetchTimeline(route.params.subscriptionId);
}

async function confirmArchive() {
  await store.archiveSubscription(route.params.subscriptionId);
  showArchive.value = false;
  await router.push({ name: 'customers.subscriptions', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreSubscription(route.params.subscriptionId);
  await store.fetchTimeline(route.params.subscriptionId);
}
</script>
