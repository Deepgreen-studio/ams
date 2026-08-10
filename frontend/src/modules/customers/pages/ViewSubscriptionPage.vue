<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <template v-if="subscription">
        <RouterLink
          :to="{ name: 'customers.subscriptions', params: { id: route.params.id } }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="openEdit"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </button>
        <button
          v-if="subscription.status !== 'cancelled' && !subscription.deleted_at"
          type="button"
          class="rounded-[12px] border border-amber-300 px-5 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-50"
          :disabled="store.saving"
          @click="cancelSubscription"
        >
          Cancel plan
        </button>
        <button
          v-if="subscription.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Delete
        </button>
      </template>
    </Teleport>

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div
      v-if="store.loading && !subscription"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="subscription" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex flex-wrap items-start gap-4">
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[14px] bg-brand-50 text-base font-semibold text-brand-700"
            >
              {{ initials(subscription.plan_name) }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 class="text-xl font-semibold text-slate-900">
                    {{ subscription.plan_name || 'Subscription' }}
                  </h2>
                  <p class="mt-1 text-sm capitalize text-slate-500">
                    {{ formatLabel(subscription.plan_type) }}
                  </p>
                </div>
                <div class="flex flex-wrap gap-2">
                  <SubscriptionStatusBadge :status="subscription.status" />
                  <PaymentStatusBadge :status="subscription.payment_status" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Subscription details</h3>
          <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="item in detailItems"
              :key="item.label"
              class="grid grid-cols-[8.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[10rem_1fr]"
            >
              <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
              <dd class="text-sm font-medium text-slate-900 whitespace-pre-wrap">
                {{ item.value }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-900">Licenses</h3>
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
          <ul v-if="subscription.licenses?.length" class="mt-4 divide-y divide-zinc-100">
            <li
              v-for="license in subscription.licenses"
              :key="license.uuid"
              class="flex flex-wrap items-center justify-between gap-2 py-3"
            >
              <div>
                <p class="font-mono text-sm text-slate-900">{{ license.license_key }}</p>
                <div class="mt-1">
                  <LicenseStatusBadge :status="license.status" />
                </div>
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

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Timeline</h3>
          <ol
            v-if="store.timeline.length"
            class="relative mt-6 space-y-5 border-l border-zinc-100 pl-6"
          >
            <li v-for="(item, index) in store.timeline" :key="index" class="relative">
              <span
                class="absolute -left-[1.55rem] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand-500 ring-1 ring-brand-200"
              />
              <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
                <p class="text-sm font-medium text-slate-900">
                  {{ item.description || item.event || 'Activity' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
              </div>
            </li>
          </ol>
          <p v-else class="mt-3 text-sm text-slate-500">No timeline entries yet.</p>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Billing</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Amount</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ subscription.currency }} {{ subscription.amount ?? '0.00' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Provider</dt>
              <dd class="text-sm font-medium uppercase text-slate-900">
                {{ subscription.payment_provider || '—' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Starts</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(subscription.starts_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Renews</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(subscription.renews_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Expires</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(subscription.expires_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Record</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(subscription.created_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(subscription.updated_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Deleted</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ subscription.deleted_at ? formatDate(subscription.deleted_at) : '—' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <SubscriptionFormModal
      :open="formOpen"
      :loading="store.saving"
      :subscription="subscription"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="showDelete"
      title="Delete subscription"
      :message="`Soft delete ${subscription?.plan_name || 'this subscription'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import LicenseStatusBadge from '@/modules/customers/components/LicenseStatusBadge.vue';
import PaymentStatusBadge from '@/modules/customers/components/PaymentStatusBadge.vue';
import SubscriptionFormModal from '@/modules/customers/components/SubscriptionFormModal.vue';
import SubscriptionStatusBadge from '@/modules/customers/components/SubscriptionStatusBadge.vue';
import { useSubscriptionsStore } from '@/modules/customers/stores/subscriptions';

const route = useRoute();
const router = useRouter();
const store = useSubscriptionsStore();
const showDelete = ref(false);
const formOpen = ref(false);

const subscription = computed(() => store.currentSubscription);

const detailItems = computed(() => [
  { label: 'Plan type', value: formatLabel(subscription.value?.plan_type) },
  {
    label: 'Amount',
    value: `${subscription.value?.currency || 'USD'} ${subscription.value?.amount ?? '0.00'}`,
  },
  { label: 'Starts', value: formatDate(subscription.value?.starts_at) },
  { label: 'Expires', value: formatDate(subscription.value?.expires_at) },
  { label: 'Renews', value: formatDate(subscription.value?.renews_at) },
  {
    label: 'External IDs',
    value: subscription.value?.external_subscription_id || 'Not linked (Stripe-ready)',
  },
  {
    label: 'Features',
    value: (subscription.value?.features || []).join(', ') || '—',
  },
  { label: 'Notes', value: subscription.value?.notes || '—' },
]);

onMounted(async () => {
  await store.fetchSubscription(route.params.subscriptionId);
  await store.fetchTimeline(route.params.subscriptionId);
});

function initials(name) {
  return String(name || 'S')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function formatLabel(value) {
  return (value || '—').replaceAll('_', ' ');
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function openEdit() {
  store.clearMessages();
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  store.clearMessages();
}

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

async function onSave(payload) {
  try {
    await store.updateSubscription(route.params.subscriptionId, sanitize(payload));
    formOpen.value = false;
    await store.fetchTimeline(route.params.subscriptionId);
  } catch {
    // Field errors stay in the modal via the store.
  }
}

async function cancelSubscription() {
  const reason = window.prompt('Cancellation reason (optional)') || '';
  await store.cancelSubscription(route.params.subscriptionId, reason);
  await store.fetchTimeline(route.params.subscriptionId);
}

async function confirmDelete() {
  await store.archiveSubscription(route.params.subscriptionId);
  showDelete.value = false;
  await router.push({ name: 'customers.subscriptions', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreSubscription(route.params.subscriptionId);
  await store.fetchTimeline(route.params.subscriptionId);
}
</script>
