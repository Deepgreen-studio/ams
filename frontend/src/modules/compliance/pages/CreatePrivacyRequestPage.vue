<template>
  <div>
    <!-- <PageHeader
      title="Create privacy request"
      description="Log an access, export, deletion, restriction, objection, consent, or portability request."
    /> -->
    <ComplianceSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <PrivacyRequestForm
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Create request"
        @submit="onSubmit"
        @cancel="router.push({ name: 'compliance.privacy.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyRequestForm from '@/modules/compliance/components/PrivacyRequestForm.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';

const router = useRouter();
const store = usePrivacyRequestsStore();

async function onSubmit(payload) {
  const created = await store.createRequest(payload);
  await router.push({ name: 'compliance.privacy.show', params: { id: created.uuid } });
}
</script>
