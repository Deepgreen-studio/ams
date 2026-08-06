<template>
  <div>
    <PageHeader title="License history" :description="`Active and archived licenses for ${customerName}.`">
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to licenses
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <LicenseTable
      :licenses="store.history"
      :loading="store.loading"
      :customer-id="route.params.id"
    />

    <div class="mt-4">
      <Pagination :meta="store.historyMeta" :loading="store.loading" @change="onPageChange" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import LicenseTable from '@/modules/customers/components/LicenseTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useLicensesStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchHistory({ customer: route.params.id, page: 1 });
});

function onPageChange(page) {
  store.fetchHistory({ customer: route.params.id, page });
}
</script>
