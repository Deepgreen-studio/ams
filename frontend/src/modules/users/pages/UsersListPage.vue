<template>
  <div>
    <!-- <PageHeader title="Users" description="Manage platform users, status, and profiles.">
      <template #actions>
        <RouterLink
          :to="{ name: 'users.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create user
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'users.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create user
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

    <div v-if="usersStore.statistics" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="space-y-4">
      <UserSearchFilter :model-value="usersStore.filters" @submit="onFilter" @reset="onReset" />

      <div
        v-if="usersStore.hasSelection"
        class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600"
      >
        {{ usersStore.selectedIds.length }} selected
        <span class="text-slate-400">(bulk actions ready for a future milestone)</span>
      </div>

      <UserTable
        :users="usersStore.users"
        :selected-ids="usersStore.selectedIds"
        :loading="usersStore.loading"
        :sort-by="usersStore.filters.sort_by"
        :sort-dir="usersStore.filters.sort_dir"
        @toggle="usersStore.toggleSelection"
        @toggle-all="onToggleAll"
        @sort="onSort"
        @delete="openDelete"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'users.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create user
          </RouterLink>
        </template>
      </UserTable>

      <Pagination :meta="usersStore.meta" :loading="usersStore.loading" @change="onPageChange" />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete user"
      :message="`Soft delete ${pendingDelete?.full_name || 'this user'}? They can be restored later.`"
      confirm-label="Delete"
      :loading="usersStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import UserSearchFilter from '@/modules/users/components/UserSearchFilter.vue';
import UserTable from '@/modules/users/components/UserTable.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const usersStore = useUsersStore();
const pendingDelete = ref(null);

const statCards = computed(() => [
  { label: 'Total', value: usersStore.statistics?.total ?? 0 },
  { label: 'Active', value: usersStore.statistics?.active ?? 0 },
  { label: 'Inactive', value: usersStore.statistics?.inactive ?? 0 },
  { label: 'Suspended', value: usersStore.statistics?.suspended ?? 0 },
  { label: 'Trashed', value: usersStore.statistics?.trashed ?? 0 },
]);

onMounted(() => {
  usersStore.fetchUsers();
});

function onFilter(filters) {
  usersStore.fetchUsers(filters);
}

function onReset() {
  usersStore.resetFilters();
  usersStore.fetchUsers();
}

function onPageChange(page) {
  usersStore.fetchUsers({ page });
}

function onSort(column) {
  const sortDir =
    usersStore.filters.sort_by === column && usersStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';

  usersStore.fetchUsers({ sort_by: column, sort_dir: sortDir, page: 1 });
}

function onToggleAll() {
  usersStore.toggleSelectAll(usersStore.users.map((user) => user.uuid));
}

function openDelete(user) {
  pendingDelete.value = user;
}

async function confirmDelete() {
  if (!pendingDelete.value) {
    return;
  }

  await usersStore.deleteUser(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await usersStore.fetchUsers();
}
</script>
