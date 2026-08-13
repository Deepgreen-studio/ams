<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.breaches.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.breaches.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ShieldExclamationIcon class="h-4 w-4" />
        All incidents
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Report data breach</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Capture incident details for risk assessment and notification workflows.
        </p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <BreachForm
          :loading="store.saving"
          :field-errors="store.fieldErrors"
          @submit="onSubmit"
          @cancel="router.push({ name: 'compliance.breaches.index' })"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { ShieldExclamationIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import BreachForm from '@/modules/compliance/components/BreachForm.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const router = useRouter();
const store = useDataBreachStore();
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
    const created = await store.createBreach(payload);
    toast.success(store.successMessage || 'Incident reported successfully.');
    store.successMessage = null;
    await router.push({ name: 'compliance.breaches.show', params: { id: created.uuid } });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
