<template>
  <div>
    <!-- <PageHeader :title="title" description="Semantic version catalog for this application.">
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create version
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
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

    <div
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:flex-row lg:items-end"
    >
      <div class="min-w-[12rem] flex-1">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="localSearch"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          placeholder="Version, build, notes..."
        />
      </div>
      <div class="w-full lg:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Status</label
        >
        <select
          v-model="localStatus"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="draft">Draft</option>
          <option value="testing">Testing</option>
          <option value="beta">Beta</option>
          <option value="production">Production</option>
          <option value="deprecated">Deprecated</option>
          <option value="rollback">Rollback</option>
        </select>
      </div>
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="applyFilters"
      >
        Filter
      </button>
    </div>

    <VersionTable
      :application-id="route.params.id"
      :versions="versionsStore.versions"
      :loading="versionsStore.loading"
      @delete="openDelete"
    >
      <template #empty-action>
        <RouterLink
          :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create version
        </RouterLink>
      </template>
    </VersionTable>

    <div class="mt-4">
      <Pagination
        :meta="versionsStore.meta"
        :loading="versionsStore.loading"
        @change="onPageChange"
      />
    </div>

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
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionTable from '@/modules/applications/components/VersionTable.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const versionsStore = useVersionsStore();
const pendingDelete = ref(null);
const localSearch = ref('');
const localStatus = ref('');

const title = computed(() => {
  const name = versionsStore.application?.name;
  return name ? `${name} versions` : 'Versions';
});

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

function onPageChange(page) {
  versionsStore.fetchVersions(route.params.id, { page });
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
