import api from '@/services/api';

export const mappingService = {
  list(params = {}) {
    return api.get('/mappings', { params });
  },
  catalogs() {
    return api.get('/mappings/catalogs');
  },
  get(id) {
    return api.get(`/mappings/${id}`);
  },
  create(payload) {
    return api.post('/mappings', payload);
  },
  update(id, payload) {
    return api.put(`/mappings/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/mappings/${id}`);
  },
  preview(id, payload = {}) {
    return api.post(`/mappings/${id}/preview`, payload);
  },
  validate(id, payload = {}) {
    return api.post(`/mappings/${id}/validate`, payload);
  },
};
