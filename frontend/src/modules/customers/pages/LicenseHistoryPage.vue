<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to licenses
      </RouterLink>
    </Teleport>

    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <LicenseTable
      :licenses="store.history"
      :loading="store.loading"
      :customer-id="route.params.id"
      @edit="openEdit"
      @delete="openDelete"
    >
      <template #footer>
        <Pagination
          :meta="store.historyMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </LicenseTable>

    <LicenseFormModal
      :open="formOpen"
      :loading="store.saving"
      :license="editingLicense"
      :customer-id="route.params.id"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete license"
      :message="`Soft delete ${pendingDelete?.license_key || 'this license'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import LicenseFormModal from '@/modules/customers/components/LicenseFormModal.vue';
import LicenseTable from '@/modules/customers/components/LicenseTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useLicensesStore();
const editingLicense = ref(null);
const formOpen = ref(false);
const pendingDelete = ref(null);

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  store.resetFilters(route.params.id);
  await store.fetchHistory({ customer: route.params.id, page: 1 });
});

function onPageChange(page) {
  store.fetchHistory({ customer: route.params.id, page });
}

function onPerPageChange(perPage) {
  store.fetchHistory({ customer: route.params.id, per_page: perPage, page: 1 });
}

function openEdit(item) {
  store.clearMessages();
  editingLicense.value = item;
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  editingLicense.value = null;
  store.clearMessages();
}

function sanitize(payload) {
  const next = { ...payload };
  delete next.subscription_id;
  ['starts_at', 'expires_at', 'notes'].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.starts_at) next.starts_at = new Date(next.starts_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  next.max_activations = Number(next.max_activations || 5);
  return next;
}

async function onSave(payload) {
  try {
    await store.updateLicense(editingLicense.value.uuid, sanitize(payload));
    formOpen.value = false;
    editingLicense.value = null;
    await store.fetchHistory({ customer: route.params.id });
  } catch {
    // Field errors stay in the modal via the store.
  }
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.archiveLicense(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await store.fetchHistory({ customer: route.params.id });
}
</script>
