<template>
  <div>
    <!-- <PageHeader title="Application history" :description="`Assignment history for ${customerName}.`">
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.applications', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to assignments
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'customers.applications', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to assignments
        </RouterLink>
    </Teleport>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="space-y-4">
      <AssignmentSearchFilter :model-value="store.filters" @submit="onFilter" @reset="onReset" />

      <AssignmentTable
        :assignments="store.history"
        :loading="store.loading"
        :customer-id="route.params.id"
      />

      <Pagination :meta="store.historyMeta" :loading="store.loading" @change="onPageChange" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import AssignmentSearchFilter from '@/modules/customers/components/AssignmentSearchFilter.vue';
import AssignmentTable from '@/modules/customers/components/AssignmentTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCustomerApplicationsStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchHistory({ customer: route.params.id, page: 1, trashed: 'with' });
});

function onFilter(filters) {
  store.fetchHistory({ ...filters, customer: route.params.id, trashed: filters.trashed || 'with' });
}

function onReset() {
  store.resetFilters(route.params.id);
  store.fetchHistory({ customer: route.params.id, trashed: 'with' });
}

function onPageChange(page) {
  store.fetchHistory({ customer: route.params.id, page, trashed: 'with' });
}
</script>
