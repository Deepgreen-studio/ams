import api from '@/services/api';

export const webhookService = {
  list(params = {}) {
    return api.get('/webhooks', { params });
  },
  get(id) {
    return api.get(`/webhooks/${id}`);
  },
  create(payload) {
    return api.post('/webhooks', payload);
  },
  update(id, payload) {
    return api.put(`/webhooks/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/webhooks/${id}`);
  },
  test(id, payload = {}) {
    return api.post(`/webhooks/${id}/test`, payload);
  },
  logs(params = {}) {
    return api.get('/webhooks/logs', { params });
  },
  log(id) {
    return api.get(`/webhooks/logs/${id}`);
  },
  retry(id) {
    return api.post(`/webhooks/logs/${id}/retry`);
  },
  events(params = {}) {
    return api.get('/webhooks/events', { params });
  },
  event(id) {
    return api.get(`/webhooks/events/${id}`);
  },
};
