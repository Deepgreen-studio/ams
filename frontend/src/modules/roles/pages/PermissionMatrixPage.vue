<template>
  <div>
    <div class="mb-4 rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
      <label class="mb-2 block text-sm font-medium text-slate-700">Role</label>
      <SelectBox
        v-model="selectedRole"
        :options="roleOptions"
        placeholder="Select a role"
        size="lg"
        wrapper-class="max-w-md"
        @change="loadMatrix"
      />
    </div>

    <PermissionMatrix :matrix="permissionsStore.matrix" :loading="permissionsStore.loading" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import PermissionMatrix from '@/modules/roles/components/PermissionMatrix.vue';
import { usePermissionsStore, useRolesStore } from '@/modules/roles/stores/roles';

const rolesStore = useRolesStore();
const permissionsStore = usePermissionsStore();
const selectedRole = ref('');

const roleOptions = computed(() =>
  (rolesStore.roles || []).map((role) => ({
    value: role.uuid,
    label: role.display_name || role.name,
  }))
);

onMounted(async () => {
  await rolesStore.fetchRoles({ per_page: 100 });
  if (rolesStore.roles[0]) {
    selectedRole.value = rolesStore.roles[0].uuid;
    await loadMatrix();
  }
});

async function loadMatrix() {
  if (!selectedRole.value) {
    return;
  }
  await permissionsStore.fetchMatrix(selectedRole.value);
}
</script>
