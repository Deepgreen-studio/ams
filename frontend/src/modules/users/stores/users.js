import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { userService } from '@/modules/users/services/userService';

const defaultFilters = () => ({
  search: '',
  status: '',
  created_from: '',
  created_to: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
  trashed: '',
});

export const useUsersStore = defineStore('users', () => {
  const users = ref([]);
  const meta = ref(null);
  const statistics = ref(null);
  const currentUser = ref(null);
  const activitySummary = ref(null);
  const profile = ref(null);
  const filters = ref(defaultFilters());
  const selectedIds = ref([]);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  const hasSelection = computed(() => selectedIds.value.length > 0);
  const totalUsers = computed(() => meta.value?.total ?? 0);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  async function fetchUsers(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );

      const { data } = await userService.list(params);
      users.value = data.data?.users?.items ?? [];
      meta.value = data.data?.users?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load users');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchUser(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await userService.get(id);
      currentUser.value = data.data?.user ?? null;
      activitySummary.value = data.data?.activity_summary ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load user');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createUser(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.create(payload);
      successMessage.value = data.message || 'User created successfully.';
      return data.data?.user;
    } catch (err) {
      applyError(err, 'Unable to create user');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateUser(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.update(id, payload);
      currentUser.value = data.data?.user ?? currentUser.value;
      successMessage.value = data.message || 'User updated successfully.';
      return data.data?.user;
    } catch (err) {
      applyError(err, 'Unable to update user');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function deleteUser(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.remove(id);
      successMessage.value = data.message || 'User deleted successfully.';
      selectedIds.value = selectedIds.value.filter((value) => value !== id);
      return data;
    } catch (err) {
      applyError(err, 'Unable to delete user');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreUser(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.restore(id);
      successMessage.value = data.message || 'User restored successfully.';
      return data.data?.user;
    } catch (err) {
      applyError(err, 'Unable to restore user');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function forceDeleteUser(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.forceDelete(id);
      successMessage.value = data.message || 'User permanently deleted.';
      selectedIds.value = selectedIds.value.filter((value) => value !== id);
      return data;
    } catch (err) {
      applyError(err, 'Unable to permanently delete user');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchProfile() {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await userService.profile();
      profile.value = data.data?.user ?? null;
      return profile.value;
    } catch (err) {
      applyError(err, 'Unable to load profile');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateProfile(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.updateProfile(payload);
      profile.value = data.data?.user ?? null;
      successMessage.value = data.message || 'Profile updated successfully.';
      return profile.value;
    } catch (err) {
      applyError(err, 'Unable to update profile');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function uploadAvatar(file) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await userService.uploadAvatar(file);
      profile.value = data.data?.user ?? profile.value;
      successMessage.value = data.message || 'Avatar updated successfully.';
      return profile.value;
    } catch (err) {
      applyError(err, 'Unable to upload avatar');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function toggleSelection(id) {
    if (selectedIds.value.includes(id)) {
      selectedIds.value = selectedIds.value.filter((value) => value !== id);
      return;
    }

    selectedIds.value = [...selectedIds.value, id];
  }

  function toggleSelectAll(ids = []) {
    if (ids.length && ids.every((id) => selectedIds.value.includes(id))) {
      selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
      return;
    }

    selectedIds.value = [...new Set([...selectedIds.value, ...ids])];
  }

  function clearSelection() {
    selectedIds.value = [];
  }

  function resetFilters() {
    filters.value = defaultFilters();
  }

  return {
    users,
    meta,
    statistics,
    currentUser,
    activitySummary,
    profile,
    filters,
    selectedIds,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    hasSelection,
    totalUsers,
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    restoreUser,
    forceDeleteUser,
    fetchProfile,
    updateProfile,
    uploadAvatar,
    toggleSelection,
    toggleSelectAll,
    clearSelection,
    resetFilters,
    clearMessages,
  };
});
