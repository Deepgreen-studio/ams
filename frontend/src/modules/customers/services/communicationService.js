import api from '@/services/api';

export const communicationCenterService = {
  overview(params = {}) {
    return api.get('/customer-communication-center/overview', { params });
  },
  timeline(params = {}) {
    return api.get('/customer-communication-center/timeline', { params });
  },
  activity(params = {}) {
    return api.get('/customer-communication-center/activity', { params });
  },
  calendar(params = {}) {
    return api.get('/customer-communication-center/calendar', { params });
  },
};

export const customerNoteService = {
  list(params = {}) {
    return api.get('/customer-notes', { params });
  },
  get(id) {
    return api.get(`/customer-notes/${id}`);
  },
  create(payload) {
    return api.post('/customer-notes', payload);
  },
  update(id, payload) {
    return api.put(`/customer-notes/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/customer-notes/${id}`);
  },
};

export const customerTaskService = {
  list(params = {}) {
    return api.get('/customer-tasks', { params });
  },
  calendar(params = {}) {
    return api.get('/customer-tasks/calendar', { params });
  },
  get(id) {
    return api.get(`/customer-tasks/${id}`);
  },
  create(payload) {
    return api.post('/customer-tasks', payload);
  },
  update(id, payload) {
    return api.put(`/customer-tasks/${id}`, payload);
  },
  complete(id) {
    return api.post(`/customer-tasks/${id}/complete`);
  },
  remove(id) {
    return api.delete(`/customer-tasks/${id}`);
  },
};

export const customerCommunicationService = {
  list(params = {}) {
    return api.get('/customer-communications', { params });
  },
  get(id) {
    return api.get(`/customer-communications/${id}`);
  },
  create(payload) {
    return api.post('/customer-communications', payload);
  },
  update(id, payload) {
    return api.put(`/customer-communications/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/customer-communications/${id}`);
  },
};
