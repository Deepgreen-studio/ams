<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
      <div class="mb-5 flex items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-slate-900">
          Permissions · {{ rolesStore.currentRole?.display_name || 'Role' }}
        </h3>
        <span class="text-xs text-slate-500">{{ selectedPermissions.length }} selected</span>
      </div>

      <PermissionTree
        :groups="permissionsStore.groups"
        :selected="selectedPermissions"
        :loading="permissionsStore.loading || rolesStore.loading"
        @update:selected="(value) => (selectedPermissions = value)"
      />

      <div class="mt-6 flex justify-end gap-2">
        <RouterLink
          :to="{ name: 'roles.show', params: { id: route.params.id } }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Cancel
        </RouterLink>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="rolesStore.saving"
          @click="onSave"
        >
          {{ rolesStore.saving ? 'Saving...' : 'Save permissions' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useToast } from '@/composables/useToast';
import PermissionTree from '@/modules/roles/components/PermissionTree.vue';
import { usePermissionsStore, useRolesStore } from '@/modules/roles/stores/roles';

const route = useRoute();
const rolesStore = useRolesStore();
const permissionsStore = usePermissionsStore();
const toast = useToast();
const selectedPermissions = ref([]);

onMounted(async () => {
  rolesStore.successMessage = null;
  rolesStore.error = null;
  await Promise.all([rolesStore.fetchRole(route.params.id), permissionsStore.fetchGroups()]);
});

watch(
  () => rolesStore.currentRole,
  (role) => {
    selectedPermissions.value = (role?.permissions || []).map((permission) => permission.name);
  },
  { immediate: true }
);

watch(
  () => rolesStore.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    rolesStore.successMessage = null;
  }
);

watch(
  () => rolesStore.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    rolesStore.error = null;
  }
);

async function onSave() {
  await rolesStore.syncPermissions(route.params.id, selectedPermissions.value);
}
</script>
