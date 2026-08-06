<template>
  <div>
    <PageHeader
      title="Customers"
      description="Manage individual, business, and enterprise customers."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create customer
        </RouterLink>
      </template>
    </PageHeader>

    <div
      v-if="customersStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ customersStore.successMessage }}
    </div>
    <div
      v-if="customersStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ customersStore.error }}
    </div>

    <div v-if="customersStore.statistics" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="space-y-4">
      <CustomerSearchFilter
        :model-value="customersStore.filters"
        @submit="onFilter"
        @reset="onReset"
      />

      <CustomerTable
        :customers="customersStore.customers"
        :loading="customersStore.loading"
        @archive="openArchive"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'customers.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create customer
          </RouterLink>
        </template>
      </CustomerTable>

      <Pagination
        :meta="customersStore.meta"
        :loading="customersStore.loading"
        @change="onPageChange"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive customer"
      :message="`Archive ${pendingArchive?.display_name || 'this customer'}? They can be restored later.`"
      confirm-label="Archive"
      :loading="customersStore.saving"
      @cancel="pendingArchive = null"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CustomerSearchFilter from '@/modules/customers/components/CustomerSearchFilter.vue';
import CustomerTable from '@/modules/customers/components/CustomerTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const customersStore = useCustomersStore();
const pendingArchive = ref(null);

const statCards = computed(() => [
  { label: 'Total', value: customersStore.statistics?.total ?? 0 },
  { label: 'Active', value: customersStore.statistics?.active ?? 0 },
  { label: 'Individual', value: customersStore.statistics?.individual ?? 0 },
  { label: 'Business', value: customersStore.statistics?.business ?? 0 },
  { label: 'Enterprise', value: customersStore.statistics?.enterprise ?? 0 },
]);

onMounted(() => {
  customersStore.fetchCustomers();
});

function onFilter(filters) {
  customersStore.fetchCustomers(filters);
}

function onReset() {
  customersStore.resetFilters();
  customersStore.fetchCustomers();
}

function onPageChange(page) {
  customersStore.fetchCustomers({ page });
}

function openArchive(customer) {
  pendingArchive.value = customer;
}

async function confirmArchive() {
  if (!pendingArchive.value) {
    return;
  }

  await customersStore.archiveCustomer(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await customersStore.fetchCustomers();
}
</script>
