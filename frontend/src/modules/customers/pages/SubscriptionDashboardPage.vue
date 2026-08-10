<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
      <RouterLink
        :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Licenses
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        New subscription
      </button>
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

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-[12px] bg-white px-4 py-3 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <RenewalReminderList
      class="mb-4"
      :reminders="store.renewalReminders"
      :customer-id="route.params.id"
    />

    <SubscriptionTable
      :subscriptions="store.subscriptions"
      :loading="store.loading"
      :customer-id="route.params.id"
      @edit="openEdit"
      @delete="openDelete"
    >
      <template #toolbar>
        <SubscriptionSearchFilter
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset
        </button>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          New subscription
        </button>
      </template>

      <template #footer>
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </SubscriptionTable>

    <SubscriptionFormModal
      :open="formOpen"
      :loading="store.saving"
      :subscription="editingSubscription"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete subscription"
      :message="`Soft delete ${pendingDelete?.plan_name || 'this subscription'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import RenewalReminderList from '@/modules/customers/components/RenewalReminderList.vue';
import SubscriptionFormModal from '@/modules/customers/components/SubscriptionFormModal.vue';
import SubscriptionSearchFilter from '@/modules/customers/components/SubscriptionSearchFilter.vue';
import SubscriptionTable from '@/modules/customers/components/SubscriptionTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useSubscriptionsStore } from '@/modules/customers/stores/subscriptions';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useSubscriptionsStore();
const pendingDelete = ref(null);
const editingSubscription = ref(null);
const formOpen = ref(false);

const statCards = computed(() => {
  const stats = store.statistics || {};
  return [
    { label: 'Total', value: stats.total ?? 0 },
    { label: 'Active', value: stats.active ?? 0 },
    { label: 'Pending payment', value: stats.pending_payment ?? 0 },
    { label: 'Renewal due soon', value: stats.renewal_due_soon ?? 0 },
  ];
});

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchDashboard({ customer: route.params.id, page: 1 });
});

function onFilter(filters) {
  store.fetchDashboard({ ...filters, customer: route.params.id });
}

function onReset() {
  store.resetFilters(route.params.id);
  store.fetchDashboard({ customer: route.params.id });
}

function onPageChange(page) {
  store.fetchDashboard({ customer: route.params.id, page });
}

function onPerPageChange(perPage) {
  store.fetchDashboard({ customer: route.params.id, per_page: perPage, page: 1 });
}

function openCreate() {
  store.clearMessages();
  editingSubscription.value = null;
  formOpen.value = true;
}

function openEdit(item) {
  store.clearMessages();
  editingSubscription.value = item;
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  editingSubscription.value = null;
  store.clearMessages();
}

function sanitize(payload, isEdit = false) {
  const next = { ...payload };
  if (!isEdit) {
    next.customer_id = route.params.id;
  } else {
    delete next.issue_license;
  }

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
    if (editingSubscription.value?.uuid) {
      await store.updateSubscription(
        editingSubscription.value.uuid,
        sanitize(payload, true),
      );
    } else {
      await store.createSubscription(sanitize(payload, false));
    }
    formOpen.value = false;
    editingSubscription.value = null;
    await store.fetchDashboard({ customer: route.params.id });
  } catch {
    // Field errors stay in the modal via the store.
  }
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.archiveSubscription(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await store.fetchDashboard({ customer: route.params.id });
}
</script>
