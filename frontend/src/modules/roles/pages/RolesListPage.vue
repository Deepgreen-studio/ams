<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="canAny('roles.view', 'roles.restore', 'roles.force-delete')"
        :to="{ name: 'roles.trash' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Soft Delete
      </RouterLink>
      <RouterLink
        v-if="can('roles.view')"
        :to="{ name: 'roles.matrix' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Permission Matrix
      </RouterLink>
      <RouterLink
        v-if="can('roles.assign')"
        :to="{ name: 'roles.assign' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Assign Roles
      </RouterLink>
      <RouterLink
        v-if="can('roles.create')"
        :to="{ name: 'roles.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create Role
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

    <RoleTable
      :roles="rolesStore.roles"
      :loading="rolesStore.loading"
      :sort-by="rolesStore.filters.sort_by"
      :sort-dir="rolesStore.filters.sort_dir"
      :empty-title="emptyState.title"
      :empty-description="emptyState.description"
      @sort="onSort"
      @delete="openDelete"
    >
      <template #toolbar>
        <RoleSearchFilter :model-value="rolesStore.filters" @submit="onFilter" @reset="onReset" />
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
          :meta="rolesStore.meta"
          :loading="rolesStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </RoleTable>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Soft delete role"
      :message="`Soft delete ${pendingDelete?.display_name || 'this role'}? They can be restored later.`"
      confirm-label="Soft Delete"
      :loading="rolesStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { PlusIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import Pagination from '@/modules/users/components/Pagination.vue';
import DeleteConfirmation from '@/modules/roles/components/DeleteConfirmation.vue';
import RoleSearchFilter from '@/modules/roles/components/RoleSearchFilter.vue';
import RoleTable from '@/modules/roles/components/RoleTable.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const rolesStore = useRolesStore();
const { can, canAny } = usePermissions();
const pendingDelete = ref(null);

const TYPE_LABELS = {
  1: 'System roles',
  0: 'Custom roles',
};

const emptyState = computed(() => {
  const filters = rolesStore.filters;
  const parts = [];
  const search = String(filters.search || '').trim();

  if (search) {
    parts.push(`search "${search}"`);
  }

  if (filters.is_system !== '' && filters.is_system !== null && TYPE_LABELS[filters.is_system] !== undefined) {
    parts.push(`type ${TYPE_LABELS[filters.is_system]}`);
  }

  if (!parts.length) {
    return {
      title: 'No roles found',
      description: 'No roles have been added yet.',
      filtered: false,
    };
  }

  return {
    title: 'No roles found',
    description: `No roles found for ${joinFilterParts(parts)}.`,
    filtered: true,
  };
});

onMounted(() => {
  rolesStore.fetchRoles({ trashed: '' });
});

function joinFilterParts(parts) {
  if (parts.length === 1) {
    return parts[0];
  }

  return `${parts.slice(0, -1).join(', ')} and ${parts[parts.length - 1]}`;
}

function onFilter(filters) {
  rolesStore.fetchRoles({ ...filters, trashed: '' });
}

function onReset() {
  rolesStore.resetFilters();
  rolesStore.fetchRoles();
}

function onPageChange(page) {
  rolesStore.fetchRoles({ page });
}

function onPerPageChange(perPage) {
  rolesStore.fetchRoles({ per_page: perPage, page: 1 });
}

function onSort(column) {
  const sortDir =
    rolesStore.filters.sort_by === column && rolesStore.filters.sort_dir === 'asc' ? 'desc' : 'asc';

  rolesStore.fetchRoles({ sort_by: column, sort_dir: sortDir, page: 1 });
}

function openDelete(role) {
  pendingDelete.value = role;
}

async function confirmDelete() {
  if (!pendingDelete.value) {
    return;
  }

  await rolesStore.deleteRole(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await rolesStore.fetchRoles();
}
</script>
