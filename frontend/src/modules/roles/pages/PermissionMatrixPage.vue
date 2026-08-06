<template>
  <div>
    <PageHeader
      title="Permission matrix"
      description="Review permission assignment across modules for a selected role."
    />

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Role</label
      >
      <select
        v-model="selectedRole"
        class="w-full max-w-md h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        @change="loadMatrix"
      >
        <option value="">Select a role</option>
        <option v-for="role in rolesStore.roles" :key="role.uuid" :value="role.uuid">
          {{ role.display_name }}
        </option>
      </select>
    </div>

    <PermissionMatrix :matrix="permissionsStore.matrix" :loading="permissionsStore.loading" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import PermissionMatrix from '@/modules/roles/components/PermissionMatrix.vue';
import { usePermissionsStore, useRolesStore } from '@/modules/roles/stores/roles';

const rolesStore = useRolesStore();
const permissionsStore = usePermissionsStore();
const selectedRole = ref('');

onMounted(async () => {
  await rolesStore.fetchRoles({ per_page: 100 });
  if (rolesStore.roles[0]) {
    selectedRole.value = rolesStore.roles[0].uuid;
    await loadMatrix();
  }
});

async function loadMatrix() {
  if (!selectedRole.value) return;
  await permissionsStore.fetchMatrix(selectedRole.value);
}
</script>
