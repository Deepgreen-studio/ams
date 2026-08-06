import api from '@/services/api';

export const settingsService = {
  all() {
    return api.get('/settings');
  },
  updateGeneral(payload) {
    return api.put('/settings', payload);
  },
  getEmail() {
    return api.get('/settings/email');
  },
  updateEmail(payload) {
    return api.put('/settings/email', payload);
  },
  getStorage() {
    return api.get('/settings/storage');
  },
  updateStorage(payload) {
    return api.put('/settings/storage', payload);
  },
  getSecurity() {
    return api.get('/settings/security');
  },
  updateSecurity(payload) {
    return api.put('/settings/security', payload);
  },
  getApi() {
    return api.get('/settings/api');
  },
  updateApi(payload) {
    return api.put('/settings/api', payload);
  },
  getQueue() {
    return api.get('/settings/queue');
  },
  updateQueue(payload) {
    return api.put('/settings/queue', payload);
  },
  getCache() {
    return api.get('/settings/cache');
  },
  systemInfo() {
    return api.get('/settings/system-info');
  },
  refreshCache() {
    return api.post('/settings/refresh-cache');
  },
};

export const mediaService = {
  list(params = {}) {
    return api.get('/media', { params });
  },
  upload(formData) {
    return api.post('/media', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  remove(id) {
    return api.delete(`/media/${id}`);
  },
  listFolders(params = {}) {
    return api.get('/folders', { params });
  },
  createFolder(payload) {
    return api.post('/folders', payload);
  },
  updateFolder(id, payload) {
    return api.put(`/folders/${id}`, payload);
  },
  deleteFolder(id) {
    return api.delete(`/folders/${id}`);
  },
};
