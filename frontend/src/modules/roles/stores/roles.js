import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { roleService } from '@/modules/roles/services/roleService';
import { permissionService } from '@/modules/roles/services/permissionService';

const defaultFilters = () => ({
  search: '',
  is_system: '',
  sort_by: 'name',
  sort_dir: 'asc',
  per_page: 10,
  page: 1,
  trashed: '',
});

export const useRolesStore = defineStore('roles', () => {
  const roles = ref([]);
  const meta = ref(null);
  const currentRole = ref(null);
  const activityHistory = ref(null);
  const filters = ref(defaultFilters());
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  const totalRoles = computed(() => meta.value?.total ?? 0);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  async function fetchRoles(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await roleService.list(params);
      roles.value = data.data?.roles?.items ?? [];
      meta.value = data.data?.roles?.meta ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load roles');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchRole(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await roleService.get(id);
      currentRole.value = data.data?.role ?? null;
      activityHistory.value = data.data?.activity_history ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load role');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createRole(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await roleService.create(payload);
      successMessage.value = data.message || 'Role created successfully.';
      return data.data?.role;
    } catch (err) {
      applyError(err, 'Unable to create role');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateRole(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await roleService.update(id, payload);
      currentRole.value = data.data?.role ?? currentRole.value;
      successMessage.value = data.message || 'Role updated successfully.';
      return data.data?.role;
    } catch (err) {
      applyError(err, 'Unable to update role');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function deleteRole(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await roleService.remove(id);
      successMessage.value = data.message || 'Role deleted successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to delete role');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreRole(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await roleService.restore(id);
      successMessage.value = data.message || 'Role restored successfully.';
      return data.data?.role;
    } catch (err) {
      applyError(err, 'Unable to restore role');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function syncPermissions(id, permissions) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await roleService.syncPermissions(id, permissions);
      currentRole.value = data.data?.role ?? currentRole.value;
      successMessage.value = data.message || 'Permissions synced successfully.';
      return data.data?.role;
    } catch (err) {
      applyError(err, 'Unable to sync permissions');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function assignUserRoles(userId, roleIds) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await roleService.assignUserRoles(userId, roleIds);
      successMessage.value = data.message || 'User roles updated successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to assign roles');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters() {
    filters.value = defaultFilters();
  }

  return {
    roles,
    meta,
    currentRole,
    activityHistory,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    totalRoles,
    fetchRoles,
    fetchRole,
    createRole,
    updateRole,
    deleteRole,
    restoreRole,
    syncPermissions,
    assignUserRoles,
    resetFilters,
    clearMessages,
  };
});

export const usePermissionsStore = defineStore('permissions', () => {
  const groups = ref([]);
  const matrix = ref([]);
  const permissions = ref([]);
  const loading = ref(false);
  const error = ref(null);

  async function fetchGroups() {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await permissionService.groups();
      groups.value = data.data?.groups ?? [];
      return groups.value;
    } catch (err) {
      error.value = err?.message || 'Unable to load permission groups';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchMatrix(roleId = null) {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await permissionService.matrix(roleId);
      matrix.value = data.data?.matrix ?? [];
      return matrix.value;
    } catch (err) {
      error.value = err?.message || 'Unable to load permission matrix';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchPermissions(params = {}) {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await permissionService.list(params);
      permissions.value = data.data?.permissions?.items ?? [];
      return permissions.value;
    } catch (err) {
      error.value = err?.message || 'Unable to load permissions';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    groups,
    matrix,
    permissions,
    loading,
    error,
    fetchGroups,
    fetchMatrix,
    fetchPermissions,
  };
});
