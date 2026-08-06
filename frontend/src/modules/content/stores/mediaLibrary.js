import { defineStore } from 'pinia';
import { ref } from 'vue';
import { mediaLibraryService } from '@/modules/content/services/mediaLibraryService';

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

export const useMediaLibraryStore = defineStore('contentMediaLibrary', () => {
  const items = ref([]);
  const meta = ref(null);
  const folders = ref([]);
  const folderTree = ref([]);
  const versions = ref([]);
  const current = ref(null);
  const filters = ref({
    search: '',
    folder: '',
    type: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 24,
    page: 1,
  });
  const uploadProgress = ref(0);
  const state = useAsyncState();

  async function fetchFolders() {
    const { data } = await mediaLibraryService.folders();
    folders.value = data.data?.folders ?? [];
    return folders.value;
  }

  async function fetchFolderTree() {
    const { data } = await mediaLibraryService.folderTree();
    folderTree.value = data.data?.folders ?? [];
    return folderTree.value;
  }

  async function fetchMedia(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      if (!params.folder) {
        params.root = 1;
      }
      const { data } = await mediaLibraryService.list(params);
      items.value = data.data?.media?.items ?? [];
      meta.value = data.data?.media?.meta ?? null;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to load media library');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createFolder(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mediaLibraryService.createFolder(payload);
      state.successMessage.value = data.message || 'Folder created.';
      await Promise.all([fetchFolders(), fetchFolderTree()]);
      return data.data?.folder;
    } catch (err) {
      state.applyError(err, 'Unable to create folder');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function uploadFiles(fileList, extras = {}) {
    state.saving.value = true;
    state.clearMessages();
    uploadProgress.value = 0;
    try {
      const formData = new FormData();
      Array.from(fileList).forEach((file) => formData.append('files[]', file));
      if (filters.value.folder) formData.append('folder', filters.value.folder);

      Object.entries(extras).forEach(([key, value]) => {
        if (value == null || value === '') return;
        if (key === 'crop' && typeof value === 'object') {
          Object.entries(value).forEach(([cropKey, cropValue]) => {
            formData.append(`crop[${cropKey}]`, cropValue);
          });
          return;
        }
        formData.append(key, value);
      });

      const { data } = await mediaLibraryService.upload(formData, (event) => {
        if (!event.total) return;
        uploadProgress.value = Math.round((event.loaded / event.total) * 100);
      });
      state.successMessage.value = data.message || 'Upload complete.';
      await fetchMedia({ page: 1 });
      return data.data?.media ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to upload media');
      throw err;
    } finally {
      state.saving.value = false;
      uploadProgress.value = 0;
    }
  }

  async function updateMedia(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mediaLibraryService.update(id, payload);
      current.value = data.data?.media ?? current.value;
      state.successMessage.value = data.message || 'Media updated.';
      await fetchMedia();
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to update media');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function replaceMedia(id, file, extras = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const formData = new FormData();
      formData.append('file', file);
      Object.entries(extras).forEach(([key, value]) => {
        if (value != null && value !== '') formData.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
      });
      const { data } = await mediaLibraryService.replace(id, formData);
      current.value = data.data?.media ?? null;
      state.successMessage.value = data.message || 'File replaced.';
      await fetchMedia();
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to replace media');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchVersions(id) {
    const { data } = await mediaLibraryService.versions(id);
    versions.value = data.data?.versions ?? [];
    return versions.value;
  }

  async function restoreVersion(id, versionId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mediaLibraryService.restoreVersion(id, versionId);
      current.value = data.data?.media ?? null;
      state.successMessage.value = data.message || 'Version restored.';
      await Promise.all([fetchMedia(), fetchVersions(id)]);
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to restore media version');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteMedia(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mediaLibraryService.remove(id);
      state.successMessage.value = data.message || 'Media deleted.';
      await fetchMedia();
    } catch (err) {
      state.applyError(err, 'Unable to delete media');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    items,
    meta,
    folders,
    folderTree,
    versions,
    current,
    filters,
    uploadProgress,
    ...state,
    fetchFolders,
    fetchFolderTree,
    fetchMedia,
    createFolder,
    uploadFiles,
    updateMedia,
    replaceMedia,
    fetchVersions,
    restoreVersion,
    deleteMedia,
  };
});
