<template>
  <div>
    <PageHeader title="Edit license" description="Update license status, activations, and dates." />
    <div v-if="store.loading && !license" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <div v-else-if="license" class="rounded-xl border border-slate-200 bg-white p-6">
      <LicenseForm
        :initial="license"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="
          router.push({
            name: 'customers.licenses.show',
            params: { id: route.params.id, licenseId: route.params.licenseId },
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
import LicenseForm from '@/modules/customers/components/LicenseForm.vue';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const router = useRouter();
const store = useLicensesStore();

const license = computed(() => store.currentLicense);

onMounted(() => {
  store.fetchLicense(route.params.licenseId);
});

function sanitize(payload) {
  const next = { ...payload };
  delete next.subscription_id;
  ['starts_at', 'expires_at', 'notes'].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.starts_at) next.starts_at = new Date(next.starts_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  next.max_activations = Number(next.max_activations || 5);
  return next;
}

async function onSubmit(payload) {
  await store.updateLicense(route.params.licenseId, sanitize(payload));
  await router.push({
    name: 'customers.licenses.show',
    params: { id: route.params.id, licenseId: route.params.licenseId },
  });
}
</script>
