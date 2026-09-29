<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="canAny('users.view', 'users.restore', 'users.force-delete')"
        :to="{ name: 'users.trash' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Soft Deleted
      </RouterLink>
      <RouterLink
        v-if="can('users.create')"
        :to="{ name: 'users.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create User
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

    <div v-if="usersStore.statistics" class="mb-4 flex flex-wrap gap-3">
      <div
        v-for="card in statCards"
        :key="card.key"
        class="flex min-w-[8.75rem] flex-1 flex-col rounded-[12px] bg-white px-4 py-3.5 text-left ring-1 ring-zinc-100"
      >
        <span class="flex items-center justify-between gap-3">
          <span
            class="text-2xl font-semibold tabular-nums leading-none"
            :class="Number(card.value) > 0 ? 'text-slate-900' : 'text-slate-400'"
          >
            {{ card.value }}
          </span>
          <span
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-4 w-4" :class="card.iconColor" />
          </span>
        </span>
        <span class="mt-2 text-xs font-medium text-slate-500">{{ card.label }}</span>
      </div>
    </div>

    <UserTable
      :users="usersStore.users"
      :loading="usersStore.loading"
      :sort-by="usersStore.filters.sort_by"
      :sort-dir="usersStore.filters.sort_dir"
      :empty-title="emptyState.title"
      :empty-description="emptyState.description"
      @sort="onSort"
      @delete="openDelete"
      @action="openAction"
    >
      <template #toolbar>
        <UserSearchFilter :model-value="usersStore.filters" @submit="onFilter" @reset="onReset" />
      </template>

      <template v-if="emptyState.filtered" #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset Filter
        </button>
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
      title="Soft delete user"
      :message="`Soft delete ${pendingDelete?.full_name || 'this user'}? They can be restored later.`"
      confirm-label="Soft Delete"
      :loading="usersStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
    <DeleteConfirmation
      :open="Boolean(pendingAction)"
      :title="actionCopy.title"
      :message="actionCopy.message"
      :confirm-label="actionCopy.confirm"
      :loading="usersStore.saving"
      @cancel="pendingAction = null"
      @confirm="confirmAction"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { CheckCircleIcon, ClockIcon, ExclamationTriangleIcon, NoSymbolIcon, PauseCircleIcon, PlusIcon, TrashIcon, UsersIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import UserSearchFilter from '@/modules/users/components/UserSearchFilter.vue';
import UserTable from '@/modules/users/components/UserTable.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useUsersStore } from '@/modules/users/stores/users';

const usersStore = useUsersStore();
const { can, canAny } = usePermissions();
const pendingDelete = ref(null);
const pendingAction = ref(null);

const STATUS_LABELS = {
  active: 'Active',
  inactive: 'Inactive',
  suspended: 'Suspended',
  pending_invitation: 'Pending Invitation',
  expired: 'Expired',
};

const actionCopy = computed(() => {
  const name = pendingAction.value?.user?.full_name || 'this user';
  const type = pendingAction.value?.type;

  if (type === 'suspend') {
    return { title: 'Suspend user', message: `Suspend ${name}? They will not be able to sign in.`, confirm: 'Suspend' };
  }
  if (type === 'deactivate') {
    return { title: 'Deactivate user', message: `Deactivate ${name}? They will not be able to sign in.`, confirm: 'Deactivate' };
  }
  if (type === 'activate') {
    return { title: 'Activate user', message: `Activate ${name}? They will be able to sign in.`, confirm: 'Activate' };
  }

  return { title: 'Resend invitation', message: `Send a new one-time invitation link to ${name}?`, confirm: 'Send invitation' };
});

const emptyState = computed(() => {
  const filters = usersStore.filters;
  const parts = [];
  const search = String(filters.search || '').trim();

  if (search) {
    parts.push(`search "${search}"`);
  }

  if (filters.status && STATUS_LABELS[filters.status]) {
    parts.push(`status ${STATUS_LABELS[filters.status]}`);
  }

  if (filters.created_from) {
    parts.push(`created from ${formatFilterDate(filters.created_from)}`);
  }

  if (filters.created_to) {
    parts.push(`created to ${formatFilterDate(filters.created_to)}`);
  }

  if (!parts.length) {
    return {
      title: 'No users found',
      description: 'No users have been added yet.',
      filtered: false,
    };
  }

  return {
    title: 'No users found',
    description: `No users found for ${joinFilterParts(parts)}.`,
    filtered: true,
  };
});

const statCards = computed(() => [
  {
    key: 'total',
    label: 'Total',
    value: usersStore.statistics?.total ?? 0,
    icon: UsersIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    key: 'active',
    label: 'Active',
    value: usersStore.statistics?.active ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    key: 'pending_invitation',
    label: 'Pending',
    value: usersStore.statistics?.pending_invitation ?? usersStore.statistics?.pending ?? 0,
    icon: ClockIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    key: 'inactive',
    label: 'Inactive',
    value: usersStore.statistics?.inactive ?? 0,
    icon: NoSymbolIcon,
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-500',
  },
  {
    key: 'suspended',
    label: 'Suspended',
    value: usersStore.statistics?.suspended ?? 0,
    icon: PauseCircleIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    key: 'expired',
    label: 'Expired',
    value: usersStore.statistics?.expired ?? 0,
    icon: ExclamationTriangleIcon,
    iconBg: 'bg-orange-50',
    iconColor: 'text-orange-600',
  },
  {
    key: 'trashed',
    label: 'Trashed',
    value: usersStore.statistics?.trashed ?? 0,
    icon: TrashIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
]);

onMounted(() => {
  usersStore.fetchUsers({ trashed: '', page: usersStore.filters.page || 1 });
});

function formatFilterDate(value) {
  const [year, month, day] = String(value).split('-');
  if (!year || !month || !day) {
    return value;
  }

  const date = new Date(Number(year), Number(month) - 1, Number(day));
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleDateString();
}

function joinFilterParts(parts) {
  if (parts.length === 1) {
    return parts[0];
  }

  return `${parts.slice(0, -1).join(', ')} and ${parts[parts.length - 1]}`;
}

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

function openAction(payload) {
  pendingAction.value = payload;
}

async function confirmAction() {
  const action = pendingAction.value;
  if (!action?.user?.uuid) {
    return;
  }

  try {
    if (action.type === 'resend') {
      await usersStore.resendInvitation(action.user.uuid);
    } else {
      const status = { suspend: 'suspended', deactivate: 'inactive', activate: 'active' }[action.type];
      await usersStore.updateUser(action.user.uuid, { status });
    }
    pendingAction.value = null;
    await usersStore.fetchUsers();
  } catch {
    pendingAction.value = null;
  }
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
