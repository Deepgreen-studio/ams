import api from '@/services/api';

export const customerDocumentService = {
  list(params = {}) {
    return api.get('/customer-documents', { params });
  },

  folders(params = {}) {
    return api.get('/customer-documents/folders', { params });
  },

  statistics(params = {}) {
    return api.get('/customer-documents/statistics', { params });
  },

  get(id) {
    return api.get(`/customer-documents/${id}`);
  },

  upload(formData) {
    return api.post('/customer-documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  update(id, payload) {
    return api.put(`/customer-documents/${id}`, payload);
  },

  uploadVersion(id, formData) {
    return api.post(`/customer-documents/${id}/versions`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  versions(id) {
    return api.get(`/customer-documents/${id}/versions`);
  },

  download(id) {
    return api.get(`/customer-documents/${id}/download`, { responseType: 'blob' });
  },

  preview(id) {
    return api.get(`/customer-documents/${id}/preview`, { responseType: 'blob' });
  },

  remove(id) {
    return api.delete(`/customer-documents/${id}`);
  },

  restore(id) {
    return api.post(`/customer-documents/${id}/restore`);
  },

  timeline(id, params = {}) {
    return api.get(`/customer-documents/${id}/timeline`, { params });
  },
};
