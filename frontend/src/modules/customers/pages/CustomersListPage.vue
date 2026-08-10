<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create customer
      </RouterLink>
    </Teleport>

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
        class="rounded-[12px] bg-white px-4 py-3 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <CustomerTable
      :customers="customersStore.customers"
      :loading="customersStore.loading"
      :sort-by="customersStore.filters.sort_by"
      :sort-dir="customersStore.filters.sort_dir"
      @sort="onSort"
      @delete="openDelete"
    >
      <template #toolbar>
        <CustomerSearchFilter
          :model-value="customersStore.filters"
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
        <RouterLink
          :to="{ name: 'customers.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create customer
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="customersStore.meta"
          :loading="customersStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </CustomerTable>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete customer"
      :message="`Soft delete ${pendingDelete?.display_name || 'this customer'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="customersStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CustomerSearchFilter from '@/modules/customers/components/CustomerSearchFilter.vue';
import CustomerTable from '@/modules/customers/components/CustomerTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const customersStore = useCustomersStore();
const pendingDelete = ref(null);

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

function onPerPageChange(perPage) {
  customersStore.fetchCustomers({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    customersStore.filters.sort_by === column && customersStore.filters.sort_dir === 'asc'
      ? 'desc'
      : 'asc';

  customersStore.fetchCustomers({ sort_by: column, sort_dir: sortDir, page: 1 });
}

function openDelete(customer) {
  pendingDelete.value = customer;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await customersStore.archiveCustomer(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await customersStore.fetchCustomers();
}
</script>
