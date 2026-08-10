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
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
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

    <div v-if="usersStore.statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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

    <UserTable
      :users="usersStore.users"
      :loading="usersStore.loading"
      :sort-by="usersStore.filters.sort_by"
      :sort-dir="usersStore.filters.sort_dir"
      @sort="onSort"
      @delete="openDelete"
    >
      <template #toolbar>
        <UserSearchFilter :model-value="usersStore.filters" @submit="onFilter" @reset="onReset" />
      </template>

      <template #empty-action>
        <RouterLink
          :to="{ name: 'users.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create user
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
    </UserTable>

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
import {
  CheckCircleIcon,
  NoSymbolIcon,
  PauseCircleIcon,
  TrashIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import UserSearchFilter from '@/modules/users/components/UserSearchFilter.vue';
import UserTable from '@/modules/users/components/UserTable.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const usersStore = useUsersStore();
const pendingDelete = ref(null);

const statCards = computed(() => [
  {
    label: 'Total',
    value: usersStore.statistics?.total ?? 0,
    icon: UsersIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Active',
    value: usersStore.statistics?.active ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Inactive',
    value: usersStore.statistics?.inactive ?? 0,
    icon: NoSymbolIcon,
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-500',
  },
  {
    label: 'Suspended',
    value: usersStore.statistics?.suspended ?? 0,
    icon: PauseCircleIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    label: 'Trashed',
    value: usersStore.statistics?.trashed ?? 0,
    icon: TrashIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
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

function onPerPageChange(perPage) {
  usersStore.fetchUsers({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    usersStore.filters.sort_by === column && usersStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';

  usersStore.fetchUsers({ sort_by: column, sort_dir: sortDir, page: 1 });
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
