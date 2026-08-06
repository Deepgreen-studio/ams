<template>
  <div>
    <PageHeader title="Customer contacts" :description="`Manage contacts for ${customerName}.`">
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.contacts.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add contact
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="contactsStore.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ contactsStore.successMessage }}
    </div>
    <div v-if="contactsStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ contactsStore.error }}
    </div>

    <div class="space-y-4">
      <ContactSearchFilter
        :model-value="contactsStore.filters"
        @submit="onFilter"
        @reset="onReset"
      />

      <ContactTable
        :contacts="contactsStore.contacts"
        :loading="contactsStore.loading"
        :customer-id="route.params.id"
        @archive="openArchive"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'customers.contacts.create', params: { id: route.params.id } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Add contact
          </RouterLink>
        </template>
      </ContactTable>

      <Pagination
        :meta="contactsStore.meta"
        :loading="contactsStore.loading"
        @change="onPageChange"
      />
    </div>

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
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ContactSearchFilter from '@/modules/customers/components/ContactSearchFilter.vue';
import ContactTable from '@/modules/customers/components/ContactTable.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerContactsStore } from '@/modules/customers/stores/contacts';

const route = useRoute();
const customersStore = useCustomersStore();
const contactsStore = useCustomerContactsStore();
const pendingArchive = ref(null);

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

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

function openArchive(contact) {
  pendingArchive.value = contact;
}

async function confirmArchive() {
  if (!pendingArchive.value) return;
  await contactsStore.archiveContact(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await contactsStore.fetchContacts({ customer: route.params.id });
}
</script>
