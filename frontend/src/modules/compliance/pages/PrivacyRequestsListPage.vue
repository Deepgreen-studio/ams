<template>
  <div>
    <PageHeader
      title="Privacy requests"
      description="Search and manage GDPR access, export, deletion, and related rights requests."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.privacy.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.privacy.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New request
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
      <PrivacySearchFilters :model-value="store.filters" @submit="onFilter" @reset="onReset" />

      <PrivacyRequestTable :requests="store.requests" :loading="store.loading" @delete="openDelete">
        <template #empty-action>
          <RouterLink
            :to="{ name: 'compliance.privacy.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            New request
          </RouterLink>
        </template>
      </PrivacyRequestTable>

      <Pagination :meta="store.meta" :loading="store.loading" @change="onPageChange" />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete privacy request"
      :message="`Soft delete ${pendingDelete?.request_number || 'this request'}?`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyRequestTable from '@/modules/compliance/components/PrivacyRequestTable.vue';
import PrivacySearchFilters from '@/modules/compliance/components/PrivacySearchFilters.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = usePrivacyRequestsStore();
const pendingDelete = ref(null);

onMounted(() => {
  const queryFilters = {};
  if (route.query.status) queryFilters.status = String(route.query.status);
  if (route.query.request_type) queryFilters.request_type = String(route.query.request_type);
  if (route.query.identity_verification_status) {
    queryFilters.identity_verification_status = String(route.query.identity_verification_status);
  }
  store.fetchRequests(queryFilters);
});

function onFilter(filters) {
  store.fetchRequests(filters);
}

function onReset() {
  store.filters = {
    search: '',
    status: '',
    request_type: '',
    identity_verification_status: '',
    overdue: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  };
  store.fetchRequests();
}

function onPageChange(page) {
  store.fetchRequests({ page });
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.deleteRequest(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await store.fetchRequests();
}
</script>
