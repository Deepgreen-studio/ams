<template>
  <div>
    <PageHeader
      title="Applications"
      description="Manage customer Android, iOS, and Web applications from one dashboard."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create application
        </RouterLink>
      </template>
    </PageHeader>

    <div
      v-if="applicationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ applicationsStore.successMessage }}
    </div>
    <div
      v-if="applicationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ applicationsStore.error }}
    </div>

    <div class="space-y-4">
      <SearchFilters :model-value="applicationsStore.filters" @submit="onFilter" @reset="onReset" />

      <div class="flex justify-end">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
          <button
            type="button"
            class="rounded-md px-3 py-1.5 text-xs font-medium"
            :class="
              applicationsStore.viewMode === 'table'
                ? 'bg-brand-50 text-brand-700'
                : 'text-slate-600 hover:bg-slate-50'
            "
            @click="applicationsStore.setViewMode('table')"
          >
            Table
          </button>
          <button
            type="button"
            class="rounded-md px-3 py-1.5 text-xs font-medium"
            :class="
              applicationsStore.viewMode === 'card'
                ? 'bg-brand-50 text-brand-700'
                : 'text-slate-600 hover:bg-slate-50'
            "
            @click="applicationsStore.setViewMode('card')"
          >
            Cards
          </button>
        </div>
      </div>

      <ApplicationTable
        v-if="applicationsStore.viewMode === 'table'"
        :applications="applicationsStore.applications"
        :loading="applicationsStore.loading"
        @delete="openDelete"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'applications.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create application
          </RouterLink>
        </template>
      </ApplicationTable>

      <ApplicationCardGrid
        v-else
        :applications="applicationsStore.applications"
        :loading="applicationsStore.loading"
        @delete="openDelete"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'applications.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create application
          </RouterLink>
        </template>
      </ApplicationCardGrid>

      <Pagination
        :meta="applicationsStore.meta"
        :loading="applicationsStore.loading"
        @change="onPageChange"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete application"
      :message="`Soft delete ${pendingDelete?.name || 'this application'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="applicationsStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ApplicationCardGrid from '@/modules/applications/components/ApplicationCardGrid.vue';
import ApplicationTable from '@/modules/applications/components/ApplicationTable.vue';
import SearchFilters from '@/modules/applications/components/SearchFilters.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const applicationsStore = useApplicationsStore();
const pendingDelete = ref(null);

onMounted(() => {
  applicationsStore.fetchApplications();
});

function onFilter(filters) {
  applicationsStore.fetchApplications(filters);
}

function onReset() {
  applicationsStore.filters = {
    search: '',
    status: '',
    platform: '',
    category: '',
    visibility: '',
    company: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  };
  applicationsStore.fetchApplications();
}

function onPageChange(page) {
  applicationsStore.fetchApplications({ page });
}

function openDelete(application) {
  pendingDelete.value = application;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await applicationsStore.deleteApplication(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await applicationsStore.fetchApplications();
}
</script>
