<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="canAny('users.view', 'users.restore', 'users.force-delete')"
        :to="{ name: 'users.trash' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Trash
        <span
          v-if="usersStore.statistics?.trashed"
          class="ml-1 rounded-md bg-rose-50 px-1.5 py-0.5 text-xs font-semibold text-rose-600"
        >
          {{ usersStore.statistics.trashed }}
        </span>
      </RouterLink>
      <RouterLink
        v-if="can('users.create')"
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
      <button
        v-for="card in statCards"
        :key="card.label"
        type="button"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-8 py-7 text-left ring-1 ring-zinc-100 transition hover:ring-brand-200"
        :class="card.active ? 'ring-brand-300' : ''"
        @click="card.onClick?.()"
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
      </button>
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
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset
        </button>
        <RouterLink
          v-if="can('users.create')"
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
import { RouterLink, useRouter } from 'vue-router';
import {
  CheckCircleIcon,
  NoSymbolIcon,
  PauseCircleIcon,
  TrashIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import UserSearchFilter from '@/modules/users/components/UserSearchFilter.vue';
import UserTable from '@/modules/users/components/UserTable.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useUsersStore } from '@/modules/users/stores/users';

const router = useRouter();
const usersStore = useUsersStore();
const { can, canAny } = usePermissions();
const pendingDelete = ref(null);

const statCards = computed(() => [
  {
    label: 'Total',
    value: usersStore.statistics?.total ?? 0,
    icon: UsersIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
    active: !usersStore.filters.status,
    onClick: () => usersStore.fetchUsers({ status: '', page: 1 }),
  },
  {
    label: 'Active',
    value: usersStore.statistics?.active ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
    active: usersStore.filters.status === 'active',
    onClick: () => usersStore.fetchUsers({ status: 'active', page: 1 }),
  },
  {
    label: 'Inactive',
    value: usersStore.statistics?.inactive ?? 0,
    icon: NoSymbolIcon,
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-500',
    active: usersStore.filters.status === 'inactive',
    onClick: () => usersStore.fetchUsers({ status: 'inactive', page: 1 }),
  },
  {
    label: 'Suspended',
    value: usersStore.statistics?.suspended ?? 0,
    icon: PauseCircleIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
    active: usersStore.filters.status === 'suspended',
    onClick: () => usersStore.fetchUsers({ status: 'suspended', page: 1 }),
  },
  {
    label: 'Trashed',
    value: usersStore.statistics?.trashed ?? 0,
    icon: TrashIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
    active: false,
    onClick: () => router.push({ name: 'users.trash' }),
  },
]);

onMounted(() => {
  usersStore.fetchUsers({ trashed: '', page: usersStore.filters.page || 1 });
});

function onFilter(filters) {
  usersStore.fetchUsers({ ...filters, trashed: '' });
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
