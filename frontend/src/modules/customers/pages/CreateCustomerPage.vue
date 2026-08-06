<template>
  <div>
    <PageHeader
      title="Create customer"
      description="Register an individual, business, or enterprise customer."
    />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <CustomerForm
        :loading="customersStore.saving"
        :errors="customersStore.fieldErrors"
        :error="customersStore.error || ''"
        submit-label="Create customer"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import CustomerForm from '@/modules/customers/components/CustomerForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const router = useRouter();
const customersStore = useCustomersStore();

async function onSubmit(payload) {
  const customer = await customersStore.createCustomer(payload);
  await router.push({ name: 'customers.show', params: { id: customer.uuid } });
}
</script>
