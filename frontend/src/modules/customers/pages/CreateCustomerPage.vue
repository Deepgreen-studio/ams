<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
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
import CustomerForm from '@/modules/customers/components/CustomerForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const router = useRouter();
const customersStore = useCustomersStore();

async function onSubmit(payload) {
  const customer = await customersStore.createCustomer(payload);
  await router.push({ name: 'customers.show', params: { id: customer.uuid } });
}
</script>
