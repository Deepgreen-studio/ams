<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.cases.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <FolderIcon class="h-4 w-4" />
        All cases
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Create compliance case</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Open a new GDPR, privacy, audit, or governance case.
        </p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <CaseForm
          :loading="store.saving"
          :field-errors="store.fieldErrors"
          submit-label="Create case"
          @submit="onSubmit"
          @cancel="router.push({ name: 'compliance.cases.index' })"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { FolderIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import CaseForm from '@/modules/compliance/components/CaseForm.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';

const router = useRouter();
const store = useComplianceStore();
const toast = useToast();

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

async function onSubmit(payload) {
  try {
    const created = await store.createCase(payload);
    toast.success(store.successMessage || 'Compliance case created successfully.');
    store.successMessage = null;
    await router.push({ name: 'compliance.cases.show', params: { id: created.uuid } });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
