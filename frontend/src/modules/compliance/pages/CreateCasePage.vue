<template>
  <div>
    <PageHeader
      title="Create compliance case"
      description="Open a new GDPR, privacy, audit, or governance case."
    />
    <ComplianceSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <CaseForm
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Create case"
        @submit="onSubmit"
        @cancel="router.push({ name: 'compliance.cases.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import CaseForm from '@/modules/compliance/components/CaseForm.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';

const router = useRouter();
const store = useComplianceStore();

async function onSubmit(payload) {
  const created = await store.createCase(payload);
  await router.push({ name: 'compliance.cases.show', params: { id: created.uuid } });
}
</script>
