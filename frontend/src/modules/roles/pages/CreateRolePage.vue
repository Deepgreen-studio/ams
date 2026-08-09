<template>
  <div>
    <!-- <PageHeader
      title="Create role"
      description="Define a new role and optionally seed permissions."
    /> -->
    <div class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <RoleForm
          :loading="rolesStore.saving"
          :errors="rolesStore.fieldErrors"
          :error="rolesStore.error || ''"
          submit-label="Create role"
          @submit="onSubmit"
          @cancel="router.push({ name: 'roles.index' })"
        />
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
          Initial permissions
        </h3>
        <PermissionTree
          :groups="permissionGroups"
          :selected="selectedPermissions"
          :loading="permissionsStore.loading"
          @update:selected="(value) => (selectedPermissions = value)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import PermissionTree from '@/modules/roles/components/PermissionTree.vue';
import RoleForm from '@/modules/roles/components/RoleForm.vue';
import { usePermissionsStore, useRolesStore } from '@/modules/roles/stores/roles';

const router = useRouter();
const rolesStore = useRolesStore();
const permissionsStore = usePermissionsStore();
const selectedPermissions = ref([]);

const permissionGroups = computed(() => permissionsStore.groups);

onMounted(() => permissionsStore.fetchGroups());

async function onSubmit(payload) {
  const role = await rolesStore.createRole({
    ...payload,
    permissions: selectedPermissions.value,
  });
  await router.push({ name: 'roles.show', params: { id: role.uuid } });
}
</script>
