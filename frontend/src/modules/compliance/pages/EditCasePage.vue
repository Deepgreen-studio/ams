<template>
  <div>
    <!-- <PageHeader
      title="Edit compliance case"
      description="Update case status, priority, assignment, and due date."
    /> -->
    <ComplianceSubnav />
    <div v-if="store.loading && !current" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <CaseForm
        :initial="current || {}"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Update case"
        hide-company
        @submit="onSubmit"
        @cancel="router.push({ name: 'compliance.cases.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import CaseForm from '@/modules/compliance/components/CaseForm.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';

const route = useRoute();
const router = useRouter();
const store = useComplianceStore();

const current = computed(() => store.currentCase);

onMounted(() => {
  store.fetchCase(route.params.id);
});

async function onSubmit(payload) {
  await store.updateCase(route.params.id, payload);
  await router.push({ name: 'compliance.cases.show', params: { id: route.params.id } });
}
</script>
