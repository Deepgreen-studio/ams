<template>
  <div>
    <PageHeader
      title="Record consent"
      description="Capture marketing, analytics, push, email, SMS, or cookie consent for a subject."
    />
    <ComplianceSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <ConsentForm
        :loading="store.saving"
        :error="store.error || ''"
        @submit="onSubmit"
        @cancel="router.push({ name: 'compliance.consents.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentForm from '@/modules/compliance/components/ConsentForm.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';

const router = useRouter();
const store = useConsentStore();

async function onSubmit(payload) {
  const created = await store.createConsent(payload);
  await router.push({ name: 'compliance.consents.show', params: { id: created.uuid } });
}
</script>
