<template>
  <div>
    <div class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <RoleForm
          :loading="rolesStore.saving"
          :errors="rolesStore.fieldErrors"
          :error="rolesStore.error || ''"
          submit-label="Create role"
          @submit="onSubmit"
          @cancel="router.push({ name: 'roles.index' })"
        />
      </div>

      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <div class="mb-5 flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-slate-900">Initial permissions</h3>
          <span class="text-xs text-slate-500">{{ selectedPermissions.length }} selected</span>
        </div>
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
