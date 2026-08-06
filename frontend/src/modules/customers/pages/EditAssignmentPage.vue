<template>
  <div>
    <PageHeader
      title="Edit assignment"
      description="Update ownership, environment, status, and dates."
    />
    <div
      v-if="store.loading && !store.currentAssignment"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <AssignmentForm
        :initial="store.currentAssignment || {}"
        :customer-id="route.params.id"
        :company-id="companyId"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        hide-application
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="
          router.push({
            name: 'customers.applications.show',
            params: { id: route.params.id, assignmentId: route.params.assignmentId },
          })
        "
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import AssignmentForm from '@/modules/customers/components/AssignmentForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const store = useCustomerApplicationsStore();

const companyId = computed(
  () =>
    customersStore.currentCustomer?.company?.uuid ||
    store.currentAssignment?.customer?.company?.uuid ||
    '',
);

onMounted(async () => {
  await Promise.all([
    customersStore.fetchCustomer(route.params.id),
    store.fetchAssignment(route.params.assignmentId),
  ]);
});

function sanitize(payload) {
  const next = { ...payload };
  delete next.application_id;
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
  await store.updateAssignment(route.params.assignmentId, sanitize(payload));
  await router.push({
    name: 'customers.applications.show',
    params: { id: route.params.id, assignmentId: route.params.assignmentId },
  });
}
</script>
