<template>
  <div>
    <PageHeader
      title="Report data breach"
      description="Capture incident details for risk assessment and notification workflows."
    />
    <ComplianceSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <BreachForm
        :loading="store.saving"
        :error="store.error || ''"
        @submit="onSubmit"
        @cancel="router.push({ name: 'compliance.breaches.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import BreachForm from '@/modules/compliance/components/BreachForm.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const router = useRouter();
const store = useDataBreachStore();

async function onSubmit(payload) {
  const created = await store.createBreach(payload);
  await router.push({ name: 'compliance.breaches.show', params: { id: created.uuid } });
}
</script>
