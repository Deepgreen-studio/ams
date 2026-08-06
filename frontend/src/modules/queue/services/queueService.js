import api from '@/services/api';

export const queueService = {
  dashboard() {
    return api.get('/queue/dashboard');
  },
  statistics() {
    return api.get('/queue/statistics');
  },
  tracks(params = {}) {
    return api.get('/queue/tracks', { params });
  },
  running(params = {}) {
    return api.get('/queue/running', { params });
  },
  pending(params = {}) {
    return api.get('/queue/pending', { params });
  },
  failed(params = {}) {
    return api.get('/queue/failed', { params });
  },
  showFailed(id) {
    return api.get(`/queue/failed/${id}`);
  },
  retryFailed(id) {
    return api.post(`/queue/failed/${id}/retry`);
  },
  retryAllFailed() {
    return api.post('/queue/failed/retry-all');
  },
  forgetFailed(id) {
    return api.delete(`/queue/failed/${id}`);
  },
  flushFailed() {
    return api.delete('/queue/failed');
  },
  restart() {
    return api.post('/queue/restart');
  },
  dispatchSample(payload = {}) {
    return api.post('/queue/sample', payload);
  },
};
