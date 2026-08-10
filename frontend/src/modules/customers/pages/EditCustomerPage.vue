<template>
  <div>
    <div
      v-if="customersStore.loading && !customersStore.currentCustomer"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />
    <div v-else class="rounded-[12px] bg-white p-6 sm:p-8">
      <CustomerForm
        :initial="customersStore.currentCustomer || {}"
        :loading="customersStore.saving"
        :errors="customersStore.fieldErrors"
        :error="customersStore.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import CustomerForm from '@/modules/customers/components/CustomerForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

async function onSubmit(payload) {
  await customersStore.updateCustomer(route.params.id, payload);
  await router.push({ name: 'customers.show', params: { id: route.params.id } });
}
</script>
