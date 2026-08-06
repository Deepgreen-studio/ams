import { defineStore } from 'pinia';
import { ref } from 'vue';
import { mediaService, settingsService } from '@/modules/settings/services/settingsService';

function useAsyncState() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  return { loading, saving, error, fieldErrors, successMessage, clearMessages, applyError };
}

function extractValues(settingsMap = {}) {
  return Object.fromEntries(
    Object.entries(settingsMap).map(([key, meta]) => [key, meta?.value ?? meta]),
  );
}

export const useSettingsStore = defineStore('settings', () => {
  const groups = ref({});
  const current = ref({});
  const systemInfo = ref(null);
  const queueStatus = ref(null);
  const cacheStatus = ref(null);
  const state = useAsyncState();

  async function fetchAll() {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await settingsService.all();
      groups.value = data.data?.settings ?? {};
      return groups.value;
    } catch (err) {
      state.applyError(err, 'Unable to load settings');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchGroup(loader) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await loader();
      current.value = extractValues(data.data?.settings ?? {});
      queueStatus.value = data.data?.status ?? queueStatus.value;
      cacheStatus.value = data.data?.status ?? cacheStatus.value;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to load settings group');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function save(updater, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await updater(payload);
      current.value = extractValues(data.data?.settings ?? {});
      state.successMessage.value = data.message;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to save settings');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchSystemInfo() {
    state.loading.value = true;
    try {
      const { data } = await settingsService.systemInfo();
      systemInfo.value = data.data?.system ?? null;
      return systemInfo.value;
    } catch (err) {
      state.applyError(err, 'Unable to load system information');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  return {
    groups,
    current,
    systemInfo,
    queueStatus,
    cacheStatus,
    ...state,
    fetchAll,
    fetchGeneral: () => fetchGroup(async () => {
      const { data } = await settingsService.all();
      return { data: { data: { settings: data.data?.settings?.general ?? {} } } };
    }),
    // simplify - use dedicated endpoints
    loadEmail: () => fetchGroup(settingsService.getEmail),
    saveEmail: (payload) => save(settingsService.updateEmail, payload),
    loadStorage: () => fetchGroup(settingsService.getStorage),
    saveStorage: (payload) => save(settingsService.updateStorage, payload),
    loadSecurity: () => fetchGroup(settingsService.getSecurity),
    saveSecurity: (payload) => save(settingsService.updateSecurity, payload),
    loadApi: () => fetchGroup(settingsService.getApi),
    saveApi: (payload) => save(settingsService.updateApi, payload),
    loadQueue: () => fetchGroup(settingsService.getQueue),
    saveQueue: (payload) => save(settingsService.updateQueue, payload),
    loadCache: () => fetchGroup(settingsService.getCache),
    saveGeneral: (payload) => save(settingsService.updateGeneral, payload),
    loadGeneral: async () => {
      state.loading.value = true;
      state.clearMessages();
      try {
        const { data } = await settingsService.all();
        current.value = extractValues(data.data?.settings?.general ?? {});
        return current.value;
      } catch (err) {
        state.applyError(err, 'Unable to load settings');
        throw err;
      } finally {
        state.loading.value = false;
      }
    },
    fetchSystemInfo,
    refreshCache: async () => {
      state.saving.value = true;
      try {
        const { data } = await settingsService.refreshCache();
        state.successMessage.value = data.message;
      } catch (err) {
        state.applyError(err, 'Unable to refresh cache');
        throw err;
      } finally {
        state.saving.value = false;
      }
    },
  };
});

export const useMediaStore = defineStore('media', () => {
  const items = ref([]);
  const folders = ref([]);
  const meta = ref(null);
  const currentFolder = ref(null);
  const state = useAsyncState();

  async function fetchMedia(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await mediaService.list(params);
      items.value = data.data?.media?.items ?? [];
      meta.value = data.data?.media?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load media');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchFolders(params = {}) {
    const { data } = await mediaService.listFolders(params);
    folders.value = data.data?.folders ?? [];
    return folders.value;
  }

  async function upload(files, folderId = null) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const formData = new FormData();
      const list = Array.from(files);
      if (list.length === 1) {
        formData.append('file', list[0]);
      } else {
        list.forEach((file) => formData.append('files[]', file));
      }
      if (folderId) formData.append('folder_id', folderId);
      const { data } = await mediaService.upload(formData);
      state.successMessage.value = data.message;
      return data.data?.media ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to upload media');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function remove(id) {
    state.saving.value = true;
    try {
      await mediaService.remove(id);
    } catch (err) {
      state.applyError(err, 'Unable to delete media');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function createFolder(payload) {
    state.saving.value = true;
    try {
      const { data } = await mediaService.createFolder(payload);
      return data.data?.folder;
    } catch (err) {
      state.applyError(err, 'Unable to create folder');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteFolder(id) {
    state.saving.value = true;
    try {
      await mediaService.deleteFolder(id);
    } catch (err) {
      state.applyError(err, 'Unable to delete folder');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    items,
    folders,
    meta,
    currentFolder,
    ...state,
    fetchMedia,
    fetchFolders,
    upload,
    remove,
    createFolder,
    deleteFolder,
  };
});
