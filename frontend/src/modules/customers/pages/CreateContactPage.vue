<template>
  <div>
    <!-- <PageHeader title="Add contact" :description="`Create a contact for ${customerName}.`" /> -->
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <ContactForm
        :loading="contactsStore.saving"
        :errors="contactsStore.fieldErrors"
        :error="contactsStore.error || ''"
        submit-label="Create contact"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.contacts', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ContactForm from '@/modules/customers/components/ContactForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerContactsStore } from '@/modules/customers/stores/contacts';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const contactsStore = useCustomerContactsStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

async function onSubmit(payload) {
  const contact = await contactsStore.createContact({
    ...payload,
    customer_id: route.params.id,
  });
  await router.push({
    name: 'customers.contacts.show',
    params: { id: route.params.id, contactId: contact.uuid },
  });
}
</script>
