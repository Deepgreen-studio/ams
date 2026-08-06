import api from '@/services/api';

export const mediaLibraryService = {
  folders(params = {}) {
    return api.get('/content/media-folders', { params });
  },

  folderTree() {
    return api.get('/content/media-folders/tree');
  },

  createFolder(payload) {
    return api.post('/content/media-folders', payload);
  },

  updateFolder(id, payload) {
    return api.put(`/content/media-folders/${id}`, payload);
  },

  removeFolder(id) {
    return api.delete(`/content/media-folders/${id}`);
  },

  list(params = {}) {
    return api.get('/content/media-library', { params });
  },

  get(id) {
    return api.get(`/content/media-library/${id}`);
  },

  upload(formData, onUploadProgress) {
    return api.post('/content/media-library', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress,
    });
  },

  update(id, payload) {
    return api.put(`/content/media-library/${id}`, payload);
  },

  replace(id, formData) {
    return api.post(`/content/media-library/${id}/replace`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  versions(id) {
    return api.get(`/content/media-library/${id}/versions`);
  },

  restoreVersion(id, versionId) {
    return api.post(`/content/media-library/${id}/versions/${versionId}/restore`);
  },

  remove(id) {
    return api.delete(`/content/media-library/${id}`);
  },

  restore(id) {
    return api.post(`/content/media-library/${id}/restore`);
  },

  downloadUrl(id) {
    return `/api/v1/content/media-library/${id}/download`;
  },
};
