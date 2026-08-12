<template>
  <div>
    <!-- <PageHeader
      title="Compliance cases"
      description="Search, filter, and manage enterprise compliance cases."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.cases.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create case
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          v-if="can('compliance.create')"
          :to="{ name: 'compliance.cases.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create case
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

    <div class="space-y-4">
      <CaseSearchFilters :model-value="store.filters" @submit="onFilter" @reset="onReset" />

      <CaseTable :cases="store.cases" :loading="store.loading" @delete="openDelete">
        <template #empty-action>
          <RouterLink
            v-if="can('compliance.create')"
            :to="{ name: 'compliance.cases.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create case
          </RouterLink>
        </template>
      </CaseTable>

      <Pagination :meta="store.meta" :loading="store.loading" @change="onPageChange" />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete compliance case"
      :message="`Soft delete ${pendingDelete?.title || 'this case'}? It can be restored later.`"
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
// import PageHeader from '@/components/ui/PageHeader.vue';
import { usePermissions } from '@/composables/usePermissions';
import CaseSearchFilters from '@/modules/compliance/components/CaseSearchFilters.vue';
import CaseTable from '@/modules/compliance/components/CaseTable.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = useComplianceStore();
const { can } = usePermissions();
const pendingDelete = ref(null);

onMounted(() => {
  const queryFilters = {};
  if (route.query.status) queryFilters.status = String(route.query.status);
  if (route.query.priority) queryFilters.priority = String(route.query.priority);
  if (route.query.case_type) queryFilters.case_type = String(route.query.case_type);
  store.fetchCases(queryFilters);
});

function onFilter(filters) {
  store.fetchCases(filters);
}

function onReset() {
  store.filters = {
    search: '',
    status: '',
    case_type: '',
    priority: '',
    company: '',
    overdue: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  };
  store.fetchCases();
}

function onPageChange(page) {
  store.fetchCases({ page });
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.deleteCase(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await store.fetchCases();
}
</script>
