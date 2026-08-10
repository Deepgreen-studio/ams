<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.applications', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to assignments
      </RouterLink>
    </Teleport>

    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <AssignmentTable
      :assignments="store.history"
      :loading="store.loading"
      :customer-id="route.params.id"
      @edit="openEdit"
      @delete="openDelete"
    >
      <template #toolbar>
        <AssignmentSearchFilter
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
        <RouterLink
          :to="{ name: 'customers.applications', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Back to assignments
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="store.historyMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </AssignmentTable>

    <AssignmentFormModal
      :open="formOpen"
      :loading="store.saving"
      :assignment="editingAssignment"
      :customer-id="route.params.id"
      :company-id="companyId"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete assignment"
      :message="`Soft delete ${pendingDelete?.application?.name || 'this assignment'}? It can be restored later.`"
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
import AssignmentFormModal from '@/modules/customers/components/AssignmentFormModal.vue';
import AssignmentSearchFilter from '@/modules/customers/components/AssignmentSearchFilter.vue';
import AssignmentTable from '@/modules/customers/components/AssignmentTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCustomerApplicationsStore();
const editingAssignment = ref(null);
const formOpen = ref(false);
const pendingDelete = ref(null);

const companyId = computed(() => customersStore.currentCustomer?.company?.uuid || '');

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchHistory({ customer: route.params.id, page: 1, trashed: 'with' });
});

function onFilter(filters) {
  store.fetchHistory({
    ...filters,
    customer: route.params.id,
    trashed: filters.trashed || 'with',
  });
}

function onReset() {
  store.resetFilters(route.params.id);
  store.fetchHistory({ customer: route.params.id, trashed: 'with' });
}

function onPageChange(page) {
  store.fetchHistory({ customer: route.params.id, page, trashed: store.filters.trashed || 'with' });
}

function onPerPageChange(perPage) {
  store.fetchHistory({
    customer: route.params.id,
    per_page: perPage,
    page: 1,
    trashed: store.filters.trashed || 'with',
  });
}

function openEdit(item) {
  store.clearMessages();
  editingAssignment.value = item;
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  editingAssignment.value = null;
  store.clearMessages();
}

function sanitize(payload) {
  const next = { ...payload };
  delete next.application_id;
  [
    'application_environment_id',
    'integration_id',
    'owner_contact_id',
    'activated_at',
    'expires_at',
    'notes',
  ].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.activated_at) next.activated_at = new Date(next.activated_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  return next;
}

async function onSave(payload) {
  try {
    await store.updateAssignment(editingAssignment.value.uuid, sanitize(payload));
    formOpen.value = false;
    editingAssignment.value = null;
    await store.fetchHistory({
      customer: route.params.id,
      trashed: store.filters.trashed || 'with',
    });
  } catch {
    // Field errors stay in the modal via the store.
  }
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.archiveAssignment(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await store.fetchHistory({
    customer: route.params.id,
    trashed: store.filters.trashed || 'with',
  });
}
</script>
