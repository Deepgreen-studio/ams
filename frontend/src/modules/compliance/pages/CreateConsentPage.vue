<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.consents.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.consents.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <DocumentTextIcon class="h-4 w-4" />
        All consents
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Record consent</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Capture a grant or withdrawal for a subject across a marketing, analytics, or other channel.
        </p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <ConsentForm
          :loading="store.saving"
          :field-errors="store.fieldErrors"
          @submit="onSubmit"
          @cancel="router.push({ name: 'compliance.consents.index' })"
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
import ConsentForm from '@/modules/compliance/components/ConsentForm.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';

const router = useRouter();
const store = useConsentStore();
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
    const created = await store.createConsent(payload);
    toast.success(store.successMessage || 'Consent recorded successfully.');
    store.successMessage = null;
    await router.push({ name: 'compliance.consents.show', params: { id: created.uuid } });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
