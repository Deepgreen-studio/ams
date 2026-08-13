<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.policies.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.policies.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <DocumentTextIcon class="h-4 w-4" />
        All policies
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Create policy</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Draft a governed document. Later edits always create a new immutable version.
        </p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <PolicyForm
          :loading="store.saving"
          :field-errors="store.fieldErrors"
          @submit="onSubmit"
          @cancel="router.push({ name: 'compliance.policies.index' })"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { DocumentTextIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyForm from '@/modules/compliance/components/PolicyForm.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const router = useRouter();
const store = usePolicyStore();
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
    const created = await store.createPolicy(payload);
    toast.success(store.successMessage || 'Policy document created successfully.');
    store.successMessage = null;
    await router.push({ name: 'compliance.policies.show', params: { id: created.uuid } });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
