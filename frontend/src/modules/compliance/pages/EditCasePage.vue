<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="current"
        :to="{ name: 'compliance.cases.show', params: { id: current.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <EyeIcon class="h-4 w-4" />
        View case
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

    <div v-if="store.loading && !current" class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="!current"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load this case</p>
      <p class="mt-1 text-xs text-slate-500">It may have been removed, or the request failed.</p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="reload"
        >
          Retry
        </button>
        <RouterLink
          :to="{ name: 'compliance.cases.index' }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back to cases
        </RouterLink>
      </div>
    </div>

    <div v-else class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Edit compliance case</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Update case status, priority, assignment, and due date.
        </p>
      </div>
      <div class="px-6 py-6 sm:px-8">
        <CaseForm
          :initial="current"
          :loading="store.saving"
          :field-errors="store.fieldErrors"
          submit-label="Update case"
          hide-company
          @submit="onSubmit"
          @cancel="router.push({ name: 'compliance.cases.show', params: { id: route.params.id } })"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { EyeIcon, FolderIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import CaseForm from '@/modules/compliance/components/CaseForm.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';

const route = useRoute();
const router = useRouter();
const store = useComplianceStore();
const toast = useToast();

const current = computed(() => store.currentCase);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

async function reload() {
  try {
    await store.fetchCase(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});

async function onSubmit(payload) {
  try {
    await store.updateCase(route.params.id, payload);
    toast.success(store.successMessage || 'Compliance case updated successfully.');
    store.successMessage = null;
    await router.push({ name: 'compliance.cases.show', params: { id: route.params.id } });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
