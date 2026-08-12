<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="can('applications.create')"
        :to="{ name: 'applications.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create application
      </RouterLink>
    </Teleport>

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

    <div v-if="applicationsStore.statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-8 py-7 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <ApplicationTable
      :applications="applicationsStore.applications"
      :loading="applicationsStore.loading"
      :sort-by="applicationsStore.filters.sort_by"
      :sort-dir="applicationsStore.filters.sort_dir"
      @sort="onSort"
      @delete="openDelete"
    >
      <template #toolbar>
        <SearchFilters
          :model-value="applicationsStore.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset
        </button>
        <RouterLink
          v-if="can('applications.create')"
          :to="{ name: 'applications.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create application
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="applicationsStore.meta"
          :loading="applicationsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </ApplicationTable>

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
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArchiveBoxIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  NoSymbolIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ApplicationTable from '@/modules/applications/components/ApplicationTable.vue';
import SearchFilters from '@/modules/applications/components/SearchFilters.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const applicationsStore = useApplicationsStore();
const { can } = usePermissions();
const pendingDelete = ref(null);

const statCards = computed(() => [
  {
    label: 'Total',
    value: applicationsStore.statistics?.total ?? 0,
    icon: Squares2X2Icon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Active',
    value: applicationsStore.statistics?.active ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Draft',
    value: applicationsStore.statistics?.draft ?? 0,
    icon: DocumentTextIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    label: 'Inactive',
    value: applicationsStore.statistics?.inactive ?? 0,
    icon: NoSymbolIcon,
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-500',
  },
  {
    label: 'Archived',
    value: applicationsStore.statistics?.archived ?? 0,
    icon: ArchiveBoxIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
]);

onMounted(() => {
  applicationsStore.fetchApplications();
});

function onFilter(filters) {
  applicationsStore.fetchApplications(filters);
}

function onReset() {
  applicationsStore.resetFilters();
  applicationsStore.fetchApplications();
}

function onPageChange(page) {
  applicationsStore.fetchApplications({ page });
}

function onPerPageChange(perPage) {
  applicationsStore.fetchApplications({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    applicationsStore.filters.sort_by === column && applicationsStore.filters.sort_dir === 'asc'
      ? 'desc'
      : 'asc';

  applicationsStore.fetchApplications({ sort_by: column, sort_dir: sortDir, page: 1 });
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
