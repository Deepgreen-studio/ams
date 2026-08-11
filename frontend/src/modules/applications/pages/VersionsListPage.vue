<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create version
      </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="versionsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ versionsStore.successMessage }}
    </div>
    <div
      v-if="versionsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ versionsStore.error }}
    </div>

    <VersionTable
      :application-id="route.params.id"
      :versions="versionsStore.versions"
      :loading="versionsStore.loading"
      @delete="openDelete"
    >
      <template #toolbar>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="localSearch"
              type="search"
              placeholder="Version, build, notes..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="localStatus"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="resetFilters"
        >
          Reset
        </button>
        <RouterLink
          :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create version
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="versionsStore.meta"
          :loading="versionsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </VersionTable>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete version"
      :message="`Soft delete version ${pendingDelete?.version_number || ''}?`"
      confirm-label="Delete"
      :loading="versionsStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionTable from '@/modules/applications/components/VersionTable.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const versionsStore = useVersionsStore();
const pendingDelete = ref(null);
const localSearch = ref('');
const localStatus = ref('');

const statusOptions = [
  { value: '', label: 'Status: All' },
  { value: 'draft', label: 'Draft' },
  { value: 'testing', label: 'Testing' },
  { value: 'beta', label: 'Beta' },
  { value: 'production', label: 'Production' },
  { value: 'deprecated', label: 'Deprecated' },
  { value: 'rollback', label: 'Rollback' },
];

onMounted(() => {
  versionsStore.fetchVersions(route.params.id);
});

function applyFilters() {
  versionsStore.fetchVersions(route.params.id, {
    search: localSearch.value,
    status: localStatus.value,
    page: 1,
  });
}

function resetFilters() {
  localSearch.value = '';
  localStatus.value = '';
  versionsStore.fetchVersions(route.params.id, {
    search: '',
    status: '',
    page: 1,
  });
}

function onPageChange(page) {
  versionsStore.fetchVersions(route.params.id, { page });
}

function onPerPageChange(perPage) {
  versionsStore.fetchVersions(route.params.id, { per_page: perPage, page: 1 });
}

function openDelete(version) {
  pendingDelete.value = version;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await versionsStore.deleteVersion(route.params.id, pendingDelete.value.uuid);
  pendingDelete.value = null;
  await versionsStore.fetchVersions(route.params.id);
}
</script>
