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
        :to="{ name: 'customers.applications.history', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        History
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        Assign application
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

    <AssignmentTable
      :assignments="store.assignments"
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
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          Assign application
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
const pendingDelete = ref(null);
const editingAssignment = ref(null);
const formOpen = ref(false);

const companyId = computed(() => customersStore.currentCustomer?.company?.uuid || '');

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

function onPerPageChange(perPage) {
  store.fetchAssignments({ customer: route.params.id, per_page: perPage, page: 1 });
}

function openCreate() {
  store.clearMessages();
  editingAssignment.value = null;
  formOpen.value = true;
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

function sanitize(payload, isEdit = false) {
  const next = { ...payload };
  if (isEdit) {
    delete next.application_id;
  } else {
    next.customer_id = route.params.id;
  }

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
    if (editingAssignment.value?.uuid) {
      await store.updateAssignment(
        editingAssignment.value.uuid,
        sanitize(payload, true),
      );
    } else {
      await store.assignApplication(sanitize(payload, false));
    }
    formOpen.value = false;
    editingAssignment.value = null;
    await store.fetchAssignments({ customer: route.params.id });
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
  await store.fetchAssignments({ customer: route.params.id });
}
</script>
