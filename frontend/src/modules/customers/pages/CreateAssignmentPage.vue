<template>
  <div>
    <!-- <PageHeader
      title="Assign application"
      :description="`Link an application to ${customerName}.`"
    /> -->
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <AssignmentForm
        :customer-id="route.params.id"
        :company-id="companyId"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Assign application"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.applications', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AssignmentForm from '@/modules/customers/components/AssignmentForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const store = useCustomerApplicationsStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');
const companyId = computed(() => customersStore.currentCustomer?.company?.uuid || '');

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

function sanitize(payload) {
  const next = { ...payload, customer_id: route.params.id };
  [
    'application_environment_id',
    'integration_id',
    'owner_contact_id',
    'activated_at',
    'expires_at',
    'notes',
  ].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.activated_at) next.activated_at = new Date(next.activated_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  return next;
}

async function onSubmit(payload) {
  const assignment = await store.assignApplication(sanitize(payload));
  await router.push({
    name: 'customers.applications.show',
    params: { id: route.params.id, assignmentId: assignment.uuid },
  });
}
</script>
