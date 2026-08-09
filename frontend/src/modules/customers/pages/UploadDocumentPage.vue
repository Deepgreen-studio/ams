<template>
  <div>
    <!-- <PageHeader title="Upload document" :description="`Add a file to ${customerName}'s library.`" /> -->
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <DocumentUploadForm
        :default-category="route.query.category || 'contracts'"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Upload document"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.documents', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DocumentUploadForm from '@/modules/customers/components/DocumentUploadForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerDocumentsStore } from '@/modules/customers/stores/documents';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const store = useCustomerDocumentsStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

async function onSubmit(payload) {
  const formData = new FormData();
  formData.append('customer_id', route.params.id);
  formData.append('category', payload.category);
  formData.append('status', payload.status || 'active');
  formData.append('file', payload.file);
  if (payload.name) formData.append('name', payload.name);
  if (payload.notes) formData.append('notes', payload.notes);
  if (payload.expires_at) formData.append('expires_at', new Date(payload.expires_at).toISOString());

  const document = await store.uploadDocument(formData);
  await router.push({
    name: 'customers.documents.show',
    params: { id: route.params.id, documentId: document.uuid },
  });
}
</script>
