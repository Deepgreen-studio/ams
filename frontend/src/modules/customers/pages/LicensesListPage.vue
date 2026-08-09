<template>
  <div>
    <!-- <PageHeader title="License management" :description="`License keys for ${customerName}.`">
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.subscriptions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Subscriptions
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses.history', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          History
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Issue license
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'customers.subscriptions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Subscriptions
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses.history', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          History
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Issue license
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

      <LicenseSearchFilter :model-value="store.filters" @submit="onFilter" @reset="onReset" />

      <LicenseTable
        :licenses="store.licenses"
        :loading="store.loading"
        :customer-id="route.params.id"
        @archive="openArchive"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'customers.licenses.create', params: { id: route.params.id } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Issue license
          </RouterLink>
        </template>
      </LicenseTable>

      <Pagination :meta="store.meta" :loading="store.loading" @change="onPageChange" />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive license"
      :message="`Archive ${pendingArchive?.license_key || 'this license'}?`"
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
import LicenseSearchFilter from '@/modules/customers/components/LicenseSearchFilter.vue';
import LicenseTable from '@/modules/customers/components/LicenseTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useLicensesStore();
const pendingArchive = ref(null);

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

const statCards = computed(() => {
  const stats = store.statistics || {};
  return [
    { label: 'Total', value: stats.total ?? 0 },
    { label: 'Active', value: stats.active ?? 0 },
    { label: 'Revoked', value: stats.revoked ?? 0 },
    { label: 'Expired', value: stats.expired ?? 0 },
  ];
});

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchLicenses({ customer: route.params.id, page: 1 });
});

function onFilter(filters) {
  store.fetchLicenses({ ...filters, customer: route.params.id });
}

function onReset() {
  store.resetFilters(route.params.id);
  store.fetchLicenses({ customer: route.params.id });
}

function onPageChange(page) {
  store.fetchLicenses({ customer: route.params.id, page });
}

function openArchive(item) {
  pendingArchive.value = item;
}

async function confirmArchive() {
  if (!pendingArchive.value) return;
  await store.archiveLicense(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await store.fetchLicenses({ customer: route.params.id });
}
</script>
