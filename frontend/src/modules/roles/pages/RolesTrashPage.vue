<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'roles.index' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to roles
      </RouterLink>
    </Teleport>

    <div
      v-if="rolesStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ rolesStore.successMessage }}
    </div>
    <div
      v-if="rolesStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ rolesStore.error }}
    </div>

    <RoleTrashTable
      :roles="rolesStore.roles"
      :loading="rolesStore.loading"
      :sort-by="rolesStore.filters.sort_by"
      :sort-dir="rolesStore.filters.sort_dir"
      @sort="onSort"
      @restore="confirmRestore"
      @force-delete="openForceDelete"
    >
      <template #toolbar>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="search"
              type="search"
              placeholder="Search trashed roles..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applySearch"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applySearch"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetSearch"
            >
              Reset
            </button>
          </div>
        </div>
      </template>

      <template #empty-action>
        <RouterLink
          :to="{ name: 'roles.index' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Back to roles
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="rolesStore.meta"
          :loading="rolesStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </RoleTrashTable>

    <DeleteConfirmation
      :open="Boolean(pendingForceDelete)"
      title="Force delete role"
      :message="`Permanently delete ${pendingForceDelete?.display_name || 'this role'}? This cannot be undone.`"
      confirm-label="Force delete"
      :loading="rolesStore.saving"
      @cancel="pendingForceDelete = null"
      @confirm="confirmForceDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/roles/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import RoleTrashTable from '@/modules/roles/components/RoleTrashTable.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const rolesStore = useRolesStore();
const search = ref('');
const pendingForceDelete = ref(null);

onMounted(() => {
  loadTrash();
});

function loadTrash(overrides = {}) {
  return rolesStore.fetchRoles({
    trashed: 'only',
    sort_by: 'deleted_at',
    sort_dir: 'desc',
    page: 1,
    ...overrides,
  });
}

function applySearch() {
  loadTrash({ search: search.value, page: 1 });
}

function resetSearch() {
  search.value = '';
  loadTrash({ search: '', page: 1 });
}

function onPageChange(page) {
  loadTrash({ page });
}

function onPerPageChange(perPage) {
  loadTrash({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    rolesStore.filters.sort_by === column && rolesStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';
  loadTrash({ sort_by: column, sort_dir: sortDir, page: 1 });
}

async function confirmRestore(role) {
  await rolesStore.restoreRole(role.uuid);
  await loadTrash();
}

function openForceDelete(role) {
  pendingForceDelete.value = role;
}

async function confirmForceDelete() {
  if (!pendingForceDelete.value) return;
  await rolesStore.forceDeleteRole(pendingForceDelete.value.uuid);
  pendingForceDelete.value = null;
  await loadTrash();
}
</script>
