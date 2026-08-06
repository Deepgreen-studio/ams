<template>
  <div>
    <PageHeader title="Assigned applications" :description="`Applications linked to ${customerName}.`">
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.applications.history', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          History
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.applications.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Assign application
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="space-y-4">
      <AssignmentSearchFilter :model-value="store.filters" @submit="onFilter" @reset="onReset" />

      <AssignmentTable
        :assignments="store.assignments"
        :loading="store.loading"
        :customer-id="route.params.id"
        @archive="openArchive"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'customers.applications.create', params: { id: route.params.id } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Assign application
          </RouterLink>
        </template>
      </AssignmentTable>

      <Pagination :meta="store.meta" :loading="store.loading" @change="onPageChange" />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive assignment"
      :message="`Archive ${pendingArchive?.application?.name || 'this assignment'}?`"
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
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import AssignmentSearchFilter from '@/modules/customers/components/AssignmentSearchFilter.vue';
import AssignmentTable from '@/modules/customers/components/AssignmentTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCustomerApplicationsStore();
const pendingArchive = ref(null);

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchAssignments({ customer: route.params.id, page: 1 });
});

function onFilter(filters) {
  store.fetchAssignments({ ...filters, customer: route.params.id });
}

function onReset() {
  store.resetFilters(route.params.id);
  store.fetchAssignments({ customer: route.params.id });
}

function onPageChange(page) {
  store.fetchAssignments({ customer: route.params.id, page });
}

function openArchive(item) {
  pendingArchive.value = item;
}

async function confirmArchive() {
  if (!pendingArchive.value) return;
  await store.archiveAssignment(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await store.fetchAssignments({ customer: route.params.id });
}
</script>
