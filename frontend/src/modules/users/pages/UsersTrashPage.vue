<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'users.index' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to users
      </RouterLink>
    </Teleport>

    <div
      v-if="usersStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ usersStore.successMessage }}
    </div>
    <div
      v-if="usersStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ usersStore.error }}
    </div>

    <UserTrashTable
      :users="usersStore.users"
      :loading="usersStore.loading"
      :sort-by="usersStore.filters.sort_by"
      :sort-dir="usersStore.filters.sort_dir"
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
              placeholder="Search trashed users..."
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
          :to="{ name: 'users.index' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Back to users
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="usersStore.meta"
          :loading="usersStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </UserTrashTable>

    <DeleteConfirmation
      :open="Boolean(pendingForceDelete)"
      title="Force delete user"
      :message="`Permanently delete ${pendingForceDelete?.full_name || 'this user'}? This cannot be undone.`"
      confirm-label="Force delete"
      :loading="usersStore.saving"
      @cancel="pendingForceDelete = null"
      @confirm="confirmForceDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import UserTrashTable from '@/modules/users/components/UserTrashTable.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const usersStore = useUsersStore();
const search = ref('');
const pendingForceDelete = ref(null);

onMounted(() => {
  loadTrash();
});

function loadTrash(overrides = {}) {
  return usersStore.fetchUsers({
    trashed: 'only',
    status: '',
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
    usersStore.filters.sort_by === column && usersStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';
  loadTrash({ sort_by: column, sort_dir: sortDir, page: 1 });
}

async function confirmRestore(user) {
  await usersStore.restoreUser(user.uuid);
  await loadTrash();
}

function openForceDelete(user) {
  pendingForceDelete.value = user;
}

async function confirmForceDelete() {
  if (!pendingForceDelete.value) return;
  await usersStore.forceDeleteUser(pendingForceDelete.value.uuid);
  pendingForceDelete.value = null;
  await loadTrash();
}
</script>
