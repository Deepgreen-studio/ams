<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
      <button
        v-if="canExport"
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="exportContacts('csv')"
      >
        Export CSV
      </button>
      <button
        v-if="canExport"
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="exportContacts('xlsx')"
      >
        Export Excel
      </button>
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
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
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        Add Contact
      </button>
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
      v-if="contactsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ contactsStore.successMessage }}
    </div>
    <div
      v-if="contactsStore.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contactsStore.error }}
    </div>

    <ContactTable
      :contacts="contactsStore.contacts"
      :loading="contactsStore.loading"
      :customer-id="route.params.id"
      @edit="openEdit"
      @archive="openArchive"
    >
      <template #toolbar>
        <ContactSearchFilter
          :model-value="contactsStore.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </template>

      <template #empty-action>
      </template>

      <template #footer>
        <Pagination
          :meta="contactsStore.meta"
          :loading="contactsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </ContactTable>

    <ContactFormModal
      :open="formOpen"
      :loading="contactsStore.saving"
      :contact="editingContact"
      :errors="contactsStore.fieldErrors"
      :error="contactsStore.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive contact"
      :message="`Archive ${pendingArchive?.name || 'this contact'}?`"
      confirm-label="Archive"
      :loading="contactsStore.saving"
      @cancel="pendingArchive = null"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ContactFormModal from '@/modules/customers/components/ContactFormModal.vue';
import ContactSearchFilter from '@/modules/customers/components/ContactSearchFilter.vue';
import ContactTable from '@/modules/customers/components/ContactTable.vue';
import { usePermissions } from '@/composables/usePermissions';
import { customerContactService } from '@/modules/customers/services/customerContactService';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerContactsStore } from '@/modules/customers/stores/contacts';

const route = useRoute();
const customersStore = useCustomersStore();
const contactsStore = useCustomerContactsStore();
const pendingArchive = ref(null);
const editingContact = ref(null);
const formOpen = ref(false);
const { can } = usePermissions();
const canExport = computed(() => can('customers.export'));
const importInput = ref(null);
const importReport = ref(null);

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  contactsStore.resetFilters(route.params.id);
  await contactsStore.fetchContacts({ customer: route.params.id, page: 1 });
});

function onFilter(filters) {
  contactsStore.fetchContacts({ ...filters, customer: route.params.id });
}

function onReset() {
  contactsStore.resetFilters(route.params.id);
  contactsStore.fetchContacts({ customer: route.params.id });
}

function onPageChange(page) {
  contactsStore.fetchContacts({ customer: route.params.id, page });
}

function onPerPageChange(perPage) {
  contactsStore.fetchContacts({ customer: route.params.id, per_page: perPage, page: 1 });
}

function openCreate() {
  contactsStore.clearMessages();
  editingContact.value = null;
  formOpen.value = true;
}

function openEdit(contact) {
  contactsStore.clearMessages();
  editingContact.value = contact;
  formOpen.value = true;
}

function closeForm() {
  if (contactsStore.saving) return;
  formOpen.value = false;
  editingContact.value = null;
  contactsStore.clearMessages();
}

async function onSave(payload) {
  try {
    if (editingContact.value?.uuid) {
      await contactsStore.updateContact(editingContact.value.uuid, payload);
    } else {
      await contactsStore.createContact({
        ...payload,
        customer_id: route.params.id,
      });
    }
    formOpen.value = false;
    editingContact.value = null;
    await contactsStore.fetchContacts({ customer: route.params.id });
  } catch {
    // Field errors stay in the modal via the store.
  }
}

function openArchive(contact) {
  pendingArchive.value = contact;
}

async function confirmArchive() {
  if (!pendingArchive.value) return;
  await contactsStore.archiveContact(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await contactsStore.fetchContacts({ customer: route.params.id });
}

async function exportContacts(format) {
  const response = await customerContactService.exportContacts({
    customer: route.params.id,
    format,
  });
  const blob = new Blob([response.data]);
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `customer-contacts.${format === 'xlsx' ? 'xlsx' : 'csv'}`;
  link.click();
  URL.revokeObjectURL(url);
}

async function onImport(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) return;

  const formData = new FormData();
  formData.append('customer_id', route.params.id);
  formData.append('file', file);
  formData.append('update_existing', '0');

  const { data } = await customerContactService.importContacts(formData);
  importReport.value = data.data?.import ?? null;
  await contactsStore.fetchContacts({ customer: route.params.id });
}
</script>
