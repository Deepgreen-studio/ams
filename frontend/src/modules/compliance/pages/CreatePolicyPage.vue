<template>
  <div>
    <!-- <PageHeader
      title="Create policy"
      description="Draft a new governed policy document. Updates always create a new immutable version."
    /> -->
    <ComplianceSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <PolicyForm
        :loading="store.saving"
        :error="store.error || ''"
        @submit="onSubmit"
        @cancel="router.push({ name: 'compliance.policies.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyForm from '@/modules/compliance/components/PolicyForm.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const router = useRouter();
const store = usePolicyStore();

async function onSubmit(payload) {
  const created = await store.createPolicy(payload);
  await router.push({ name: 'compliance.policies.show', params: { id: created.uuid } });
}
</script>
