import api from '@/services/api';

export const customerService = {
  list(params = {}) {
    return api.get('/customers', { params });
  },

  statistics(params = {}) {
    return api.get('/customers/statistics', { params });
  },

  industries() {
    return api.get('/customers/industries');
  },

  exportCustomers(params = {}) {
    return api.get('/customers/export', { params, responseType: 'blob' });
  },

  example(params = {}) {
    return api.get('/customers/example', { params, responseType: 'blob' });
  },

  importCustomers(formData) {
    return api.post('/customers/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  console(id) {
    return api.get(`/customers/${id}/console`);
  },

  anonymize(id) {
    return api.post(`/customers/${id}/anonymize`);
  },

  get(id) {
    return api.get(`/customers/${id}`);
  },

  create(payload) {
    return api.post('/customers', payload);
  },

  update(id, payload) {
    return api.put(`/customers/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/customers/${id}`);
  },

  restore(id) {
    return api.post(`/customers/${id}/restore`);
  },

  forceDelete(id) {
    return api.delete(`/customers/${id}/force-delete`);
  },
};
