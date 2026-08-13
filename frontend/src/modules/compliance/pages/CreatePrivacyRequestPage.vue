<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.privacy.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.privacy.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <IdentificationIcon class="h-4 w-4" />
        All requests
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Create privacy request</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Log an access, export, deletion, restriction, objection, consent, or portability request.
        </p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <PrivacyRequestForm
          :loading="store.saving"
          :field-errors="store.fieldErrors"
          submit-label="Create request"
          @submit="onSubmit"
          @cancel="router.push({ name: 'compliance.privacy.index' })"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { IdentificationIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyRequestForm from '@/modules/compliance/components/PrivacyRequestForm.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';

const router = useRouter();
const store = usePrivacyRequestsStore();
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
    const created = await store.createRequest(payload);
    toast.success(store.successMessage || 'Privacy request created successfully.');
    store.successMessage = null;
    await router.push({ name: 'compliance.privacy.show', params: { id: created.uuid } });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
