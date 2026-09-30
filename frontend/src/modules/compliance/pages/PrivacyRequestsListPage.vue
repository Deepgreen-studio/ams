<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.privacy.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.privacy.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New Request
      </RouterLink>
    </Teleport>

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

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <PrivacySearchFilters :model-value="store.filters" @submit="onFilter" @reset="onReset" />
      </div>

      <PrivacyRequestTable
        :requests="store.requests"
        :loading="store.loading"
        :framed="false"
        @delete="openDelete"
      >
        <template #empty-action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset Filter
          </button>
        </template>
      </PrivacyRequestTable>

      <div class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
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
import { PlusIcon } from '@heroicons/vue/24/outline';
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { usePermissions } from '@/composables/usePermissions';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyRequestTable from '@/modules/compliance/components/PrivacyRequestTable.vue';
import PrivacySearchFilters from '@/modules/compliance/components/PrivacySearchFilters.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = usePrivacyRequestsStore();
const { can } = usePermissions();
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

function onPerPage(perPage) {
  store.fetchRequests({ per_page: perPage, page: 1 });
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
