<template>
  <div>
    <!-- <PageHeader
      title="Subscription dashboard"
      :description="`Plans, billing status, and renewals for ${customerName}.`"
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Licenses
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.subscriptions.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New subscription
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Licenses
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.subscriptions.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New subscription
        </RouterLink>
    </Teleport>

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="space-y-4">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <RenewalReminderList :reminders="store.renewalReminders" :customer-id="route.params.id" />

      <SubscriptionSearchFilter :model-value="store.filters" @submit="onFilter" @reset="onReset" />

      <SubscriptionTable
        :subscriptions="store.subscriptions"
        :loading="store.loading"
        :customer-id="route.params.id"
        @archive="openArchive"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'customers.subscriptions.create', params: { id: route.params.id } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            New subscription
          </RouterLink>
        </template>
      </SubscriptionTable>

      <Pagination :meta="store.meta" :loading="store.loading" @change="onPageChange" />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive subscription"
      :message="`Archive ${pendingArchive?.plan_name || 'this subscription'}?`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="pendingArchive = null"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import RenewalReminderList from '@/modules/customers/components/RenewalReminderList.vue';
import SubscriptionSearchFilter from '@/modules/customers/components/SubscriptionSearchFilter.vue';
import SubscriptionTable from '@/modules/customers/components/SubscriptionTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useSubscriptionsStore } from '@/modules/customers/stores/subscriptions';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useSubscriptionsStore();
const pendingArchive = ref(null);

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

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

function openArchive(item) {
  pendingArchive.value = item;
}

async function confirmArchive() {
  if (!pendingArchive.value) return;
  await store.archiveSubscription(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await store.fetchDashboard({ customer: route.params.id });
}
</script>
