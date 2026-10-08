<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="scopedCompanyId"
        :to="{ name: 'companies.show', params: { id: scopedCompanyId } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to company
      </RouterLink>
      <button
        v-if="can('customers.import')"
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="transferring"
        @click="downloadExample"
      >
        Example
      </button>
      <button
        v-if="can('customers.import')"
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="transferring"
        @click="importInput?.click()"
      >
        Import
      </button>
      <input
        ref="importInput"
        type="file"
        accept=".csv,.txt,.xlsx,.xls"
        class="hidden"
        @change="onImport"
      />
      <button
        v-if="can('customers.export')"
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="transferring"
        @click="exportCustomers"
      >
        Export
      </button>
      <RouterLink
        v-if="!scopedCompanyId && can('customers.view-trash')"
        :to="{ name: 'customers.trash' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Soft Deleted
      </RouterLink>
      <RouterLink
        v-if="can('customers.create')"
        :to="createRoute"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create Customer
      </RouterLink>
    </Teleport>

    <div
      v-if="importReport"
      class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
    >
      Created {{ importReport.created }}, updated {{ importReport.updated }}, skipped {{ importReport.skipped }}.
      <ul v-if="importReport.errors?.length" class="mt-2 list-disc pl-5 text-rose-700">
        <li v-for="error in importReport.errors" :key="`${error.row}-${error.message}`">
          Row {{ error.row }}: {{ error.message }}
        </li>
      </ul>
    </div>
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
        class="flex flex-col rounded-[12px] bg-white px-4 py-3.5 ring-1 ring-zinc-100"
      >
        <span class="flex items-center justify-between gap-3">
          <span
            class="text-2xl font-semibold tabular-nums leading-none"
            :class="Number(card.value) > 0 ? 'text-slate-900' : 'text-slate-400'"
          >
            {{ card.value }}
          </span>
          <span
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-4 w-4" :class="card.iconColor" />
          </span>
        </span>
        <span class="mt-2 text-xs font-medium text-slate-500">{{ card.label }}</span>
      </div>
    </div>

    <CustomerTable
      :customers="customersStore.customers"
      :loading="customersStore.loading"
      :sort-by="customersStore.filters.sort_by"
      :sort-dir="customersStore.filters.sort_dir"
      @sort="onSort"
      @delete="openDelete"
      @restore="confirmRestore"
    >
      <template #toolbar>
        <CustomerSearchFilter
          :model-value="customersStore.filters"
          :hide-company="Boolean(scopedCompanyId)"
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
          Reset Filter
        </button>
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
      title="Soft delete customer"
      :message="`Soft delete ${pendingDelete?.display_name || 'this customer'}? It can be restored later.`"
      confirm-label="Soft Delete"
      :loading="customersStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import {
  BuildingLibraryIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  PlusIcon,
  UserIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CustomerSearchFilter from '@/modules/customers/components/CustomerSearchFilter.vue';
import CustomerTable from '@/modules/customers/components/CustomerTable.vue';
import { customerService } from '@/modules/customers/services/customerService';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const toast = useToast();
const { can } = usePermissions();
const pendingDelete = ref(null);
const importInput = ref(null);
const importReport = ref(null);
const transferring = ref(false);

const scopedCompanyId = computed(() =>
  route.name === 'companies.customers' ? String(route.params.id || '') : '',
);

const createRoute = computed(() =>
  scopedCompanyId.value
    ? { name: 'companies.customers.create', params: { id: scopedCompanyId.value } }
    : { name: 'customers.create' },
);

const statCards = computed(() => [
  {
    label: 'Total',
    value: customersStore.statistics?.total ?? 0,
    icon: UsersIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Active',
    value: customersStore.statistics?.active ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Individual',
    value: customersStore.statistics?.individual ?? 0,
    icon: UserIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-600',
  },
  {
    label: 'Business',
    value: customersStore.statistics?.business ?? 0,
    icon: BuildingOffice2Icon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-600',
  },
  {
    label: 'Enterprise',
    value: customersStore.statistics?.enterprise ?? 0,
    icon: BuildingLibraryIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
]);

watch(
  () => [route.name, route.params.id, route.query.company],
  () => {
    const company = scopedCompanyId.value
      || (typeof route.query.company === 'string' ? route.query.company : '');
    customersStore.fetchCustomers({
      trashed: '',
      page: 1,
      company,
    });
  },
  { immediate: true },
);

function onFilter(filters) {
  customersStore.fetchCustomers({
    ...filters,
    ...(scopedCompanyId.value ? { company: scopedCompanyId.value } : {}),
  });
}

function onReset() {
  const company = scopedCompanyId.value;
  customersStore.resetFilters();
  if (!company && route.query.company) {
    router.replace({ name: 'customers.index', query: {} });
    return;
  }
  customersStore.fetchCustomers(company ? { company, page: 1 } : { page: 1 });
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

async function confirmRestore(customer) {
  await customersStore.restoreCustomer(customer.uuid);
  await customersStore.fetchCustomers();
}

function activeCompanyId() {
  return scopedCompanyId.value || customersStore.filters.company || '';
}

function saveBlob(data, filename) {
  const blob = new Blob([data]);
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

async function downloadExample() {
  transferring.value = true;

  try {
    const company = activeCompanyId();
    const response = await customerService.example(company ? { company } : {});
    saveBlob(response.data, 'customers-example.csv');
  } catch (err) {
    toast.error(err?.message || 'Unable to download the example file.', 'Example failed');
  } finally {
    transferring.value = false;
  }
}

async function exportCustomers() {
  transferring.value = true;

  try {
    const filters = customersStore.filters;
    const response = await customerService.exportCustomers({
      search: filters.search || undefined,
      status: filters.status || undefined,
      customer_type: filters.customer_type || undefined,
      company: activeCompanyId() || undefined,
      industry: filters.industry || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
      format: 'csv',
    });
    saveBlob(response.data, 'customers.csv');
  } catch (err) {
    toast.error(err?.message || 'Unable to export customers.', 'Export failed');
  } finally {
    transferring.value = false;
  }
}

async function onImport(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) return;

  transferring.value = true;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('update_existing', '1');

  const company = activeCompanyId();
  if (company) {
    formData.append('company_id', company);
  }

  try {
    const { data } = await customerService.importCustomers(formData);
    importReport.value = data.data?.import ?? null;
    toast.success(data.message || 'Customer import completed.', 'Import completed');
    await customersStore.fetchCustomers();
  } catch (err) {
    toast.error(err?.message || 'Unable to import customers.', 'Import failed');
  } finally {
    transferring.value = false;
  }
}
</script>
