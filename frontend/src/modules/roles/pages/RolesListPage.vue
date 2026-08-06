<template>
  <div>
    <PageHeader title="Roles" description="Manage enterprise roles and access control.">
      <template #actions>
        <RouterLink :to="{ name: 'roles.matrix' }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
          Permission matrix
        </RouterLink>
        <RouterLink :to="{ name: 'roles.assign' }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
          Assign roles
        </RouterLink>
        <RouterLink :to="{ name: 'roles.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
          Create role
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="rolesStore.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ rolesStore.successMessage }}
    </div>
    <div v-if="rolesStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ rolesStore.error }}
    </div>

    <div class="space-y-4">
      <RoleSearchFilter :model-value="rolesStore.filters" @submit="onFilter" @reset="onReset" />
      <RoleTable :roles="rolesStore.roles" :loading="rolesStore.loading" @delete="openDelete">
        <template #empty-action>
          <RouterLink :to="{ name: 'roles.create' }" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
            Create role
          </RouterLink>
        </template>
      </RoleTable>
      <Pagination :meta="rolesStore.meta" :loading="rolesStore.loading" @change="(page) => rolesStore.fetchRoles({ page })" />
    </div>

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
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import DeleteConfirmation from '@/modules/roles/components/DeleteConfirmation.vue';
import RoleSearchFilter from '@/modules/roles/components/RoleSearchFilter.vue';
import RoleTable from '@/modules/roles/components/RoleTable.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const rolesStore = useRolesStore();
const pendingDelete = ref(null);

onMounted(() => rolesStore.fetchRoles());

function onFilter(filters) {
  rolesStore.fetchRoles(filters);
}

function onReset() {
  rolesStore.resetFilters();
  rolesStore.fetchRoles();
}

function openDelete(role) {
  pendingDelete.value = role;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await rolesStore.deleteRole(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await rolesStore.fetchRoles();
}
</script>
