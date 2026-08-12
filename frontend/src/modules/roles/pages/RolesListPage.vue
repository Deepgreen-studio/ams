<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="canAny('roles.view', 'roles.restore', 'roles.force-delete')"
        :to="{ name: 'roles.trash' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Trash
      </RouterLink>
      <RouterLink
        v-if="can('roles.view')"
        :to="{ name: 'roles.matrix' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Permission matrix
      </RouterLink>
      <RouterLink
        v-if="can('roles.assign')"
        :to="{ name: 'roles.assign' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Assign roles
      </RouterLink>
      <RouterLink
        v-if="can('roles.create')"
        :to="{ name: 'roles.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create role
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
      @sort="onSort"
      @delete="openDelete"
    >
      <template #toolbar>
        <RoleSearchFilter :model-value="rolesStore.filters" @submit="onFilter" @reset="onReset" />
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
          v-if="can('roles.create')"
          :to="{ name: 'roles.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create role
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
    </RoleTable>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete role"
      :message="`Soft delete ${pendingDelete?.display_name || 'this role'}?`"
      confirm-label="Delete"
      :loading="rolesStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';
import Pagination from '@/modules/users/components/Pagination.vue';
import DeleteConfirmation from '@/modules/roles/components/DeleteConfirmation.vue';
import RoleSearchFilter from '@/modules/roles/components/RoleSearchFilter.vue';
import RoleTable from '@/modules/roles/components/RoleTable.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const rolesStore = useRolesStore();
const { can, canAny } = usePermissions();
const pendingDelete = ref(null);

onMounted(() => {
  rolesStore.fetchRoles({ trashed: '' });
});

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
