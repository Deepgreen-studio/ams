<template>
  <div>
    <!-- <PageHeader
      :title="`Permissions · ${rolesStore.currentRole?.display_name || 'Role'}`"
      description="Assign module permissions using the grouped checkbox tree."
    /> -->

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

    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <PermissionTree
        :groups="permissionsStore.groups"
        :selected="selectedPermissions"
        :loading="permissionsStore.loading || rolesStore.loading"
        @update:selected="(value) => (selectedPermissions = value)"
      />

      <div class="mt-6 flex justify-end gap-2">
        <RouterLink
          :to="{ name: 'roles.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Cancel
        </RouterLink>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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
// import PageHeader from '@/components/ui/PageHeader.vue';
import PermissionTree from '@/modules/roles/components/PermissionTree.vue';
import { usePermissionsStore, useRolesStore } from '@/modules/roles/stores/roles';

const route = useRoute();
const rolesStore = useRolesStore();
const permissionsStore = usePermissionsStore();
const selectedPermissions = ref([]);

onMounted(async () => {
  await Promise.all([rolesStore.fetchRole(route.params.id), permissionsStore.fetchGroups()]);
});

watch(
  () => rolesStore.currentRole,
  (role) => {
    selectedPermissions.value = (role?.permissions || []).map((permission) => permission.name);
  },
  { immediate: true },
);

async function onSave() {
  await rolesStore.syncPermissions(route.params.id, selectedPermissions.value);
}
</script>
