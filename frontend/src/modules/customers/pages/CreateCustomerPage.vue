<template>
  <div class="mx-auto w-full max-w-[1000px]">
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
      <CustomerForm
        :initial="formInitial"
        :lock-company="Boolean(lockedCompanyId)"
        :loading="customersStore.saving"
        :errors="customersStore.fieldErrors"
        :error="customersStore.error || ''"
        submit-label="Save Customer"
        @submit="onSubmit"
        @cancel="router.push(cancelRoute)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';
import CustomerForm from '@/modules/customers/components/CustomerForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const toast = useToast();

const lockedCompanyId = computed(() =>
  route.name === 'companies.customers.create' ? String(route.params.id || '') : '',
);

const formInitial = computed(() =>
  lockedCompanyId.value ? { company_id: lockedCompanyId.value } : {},
);

const cancelRoute = computed(() =>
  lockedCompanyId.value
    ? { name: 'companies.customers', params: { id: lockedCompanyId.value } }
    : { name: 'customers.index' },
);

async function onSubmit(payload) {
  const customer = await customersStore.createCustomer({
    ...payload,
    ...(lockedCompanyId.value ? { company_id: lockedCompanyId.value } : {}),
  });
  if (lockedCompanyId.value) {
    toast.success(customersStore.successMessage || 'Customer created successfully.');
    await router.push({ name: 'companies.customers', params: { id: lockedCompanyId.value } });
    return;
  }
  await router.push({ name: 'customers.show', params: { id: customer.uuid } });
}
</script>
