<template>
  <div>
    <PageHeader
      title="Consent records"
      description="Search and manage subject consent across all supported channels."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.consents.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.consents.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Record consent
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="space-y-4">
      <ConsentSearchFilters :model-value="store.filters" @submit="onFilter" @reset="onReset" />
      <ConsentTable :consents="store.consents" :loading="store.loading" @withdraw="onWithdraw">
        <template #empty-action>
          <RouterLink
            :to="{ name: 'compliance.consents.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Record consent
          </RouterLink>
        </template>
      </ConsentTable>
      <Pagination :meta="store.meta" :loading="store.loading" @change="(page) => store.fetchConsents({ page })" />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentSearchFilters from '@/modules/compliance/components/ConsentSearchFilters.vue';
import ConsentTable from '@/modules/compliance/components/ConsentTable.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useConsentStore();

onMounted(() => {
  store.fetchConsents();
});

function onFilter(filters) {
  store.fetchConsents(filters);
}

function onReset() {
  store.filters = {
    search: '',
    status: '',
    channel: '',
    source: '',
    granted: '',
    company: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  };
  store.fetchConsents();
}

async function onWithdraw(item) {
  await store.withdrawConsent(item.uuid, { notes: 'Withdrawn from consent list' });
  await store.fetchConsents();
}
</script>
