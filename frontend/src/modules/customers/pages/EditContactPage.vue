<template>
  <div>
    <!-- <PageHeader title="Edit contact" description="Update contact details and classification." /> -->
    <div
      v-if="contactsStore.loading && !contactsStore.currentContact"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <ContactForm
        :initial="contactsStore.currentContact || {}"
        :loading="contactsStore.saving"
        :errors="contactsStore.fieldErrors"
        :error="contactsStore.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="
          router.push({
            name: 'customers.contacts.show',
            params: { id: route.params.id, contactId: route.params.contactId },
          })
        "
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ContactForm from '@/modules/customers/components/ContactForm.vue';
import { useCustomerContactsStore } from '@/modules/customers/stores/contacts';

const route = useRoute();
const router = useRouter();
const contactsStore = useCustomerContactsStore();

onMounted(() => {
  contactsStore.fetchContact(route.params.contactId);
});

async function onSubmit(payload) {
  await contactsStore.updateContact(route.params.contactId, payload);
  await router.push({
    name: 'customers.contacts.show',
    params: { id: route.params.id, contactId: route.params.contactId },
  });
}
</script>
