<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.subscriptions', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Subscriptions
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        Issue license
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

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-[12px] bg-white px-4 py-3 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <LicenseTable
      :licenses="store.licenses"
      :loading="store.loading"
      :customer-id="route.params.id"
      @edit="openEdit"
      @delete="openDelete"
    >
      <template #toolbar>
        <LicenseSearchFilter
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
          Issue license
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
    </LicenseTable>

    <LicenseFormModal
      :open="formOpen"
      :loading="store.saving"
      :license="editingLicense"
      :customer-id="route.params.id"
      :default-subscription-id="defaultSubscriptionId"
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
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import LicenseFormModal from '@/modules/customers/components/LicenseFormModal.vue';
import LicenseSearchFilter from '@/modules/customers/components/LicenseSearchFilter.vue';
import LicenseTable from '@/modules/customers/components/LicenseTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useLicensesStore();
const pendingDelete = ref(null);
const editingLicense = ref(null);
const formOpen = ref(false);

const defaultSubscriptionId = computed(() => String(route.query.subscription || ''));

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

  if (route.query.subscription) {
    openCreate();
  }
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

function onPerPageChange(perPage) {
  store.fetchLicenses({ customer: route.params.id, per_page: perPage, page: 1 });
}

function openCreate() {
  store.clearMessages();
  editingLicense.value = null;
  formOpen.value = true;
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

function sanitize(payload, isEdit = false) {
  const next = { ...payload };
  if (isEdit) {
    delete next.subscription_id;
  }
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
    if (editingLicense.value?.uuid) {
      await store.updateLicense(editingLicense.value.uuid, sanitize(payload, true));
    } else {
      await store.issueLicense(sanitize(payload, false));
    }
    formOpen.value = false;
    editingLicense.value = null;
    await store.fetchLicenses({ customer: route.params.id });
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
  await store.fetchLicenses({ customer: route.params.id });
}
</script>
