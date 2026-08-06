<template>
  <div>
    <PageHeader title="Issue license" :description="`Create a license key for ${customerName}.`" />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <LicenseForm
        :default-subscription-id="route.query.subscription || ''"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Issue license"
        @submit="onSubmit"
        @cancel="router.push({ name: 'customers.licenses', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import LicenseForm from '@/modules/customers/components/LicenseForm.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const store = useLicensesStore();

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

function sanitize(payload) {
  const next = { ...payload };
  ['starts_at', 'expires_at', 'notes'].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.starts_at) next.starts_at = new Date(next.starts_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  next.max_activations = Number(next.max_activations || 5);
  return next;
}

async function onSubmit(payload) {
  const license = await store.issueLicense(sanitize(payload));
  await router.push({
    name: 'customers.licenses.show',
    params: { id: route.params.id, licenseId: license.uuid },
  });
}
</script>
