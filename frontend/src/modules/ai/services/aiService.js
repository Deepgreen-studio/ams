import api from '@/services/api';

export const aiService = {
  dashboard() {
    return api.get('/ai/dashboard');
  },

  catalog() {
    return api.get('/ai/catalog');
  },

  analytics(params = {}) {
    return api.get('/ai/analytics', { params });
  },

  settings() {
    return api.get('/ai/settings');
  },

  updateSettings(payload) {
    return api.put('/ai/settings', payload);
  },

  providers(params = {}) {
    return api.get('/ai/providers', { params });
  },

  showProvider(id) {
    return api.get(`/ai/providers/${id}`);
  },

  createProvider(payload) {
    return api.post('/ai/providers', payload);
  },

  updateProvider(id, payload) {
    return api.put(`/ai/providers/${id}`, payload);
  },

  deleteProvider(id) {
    return api.delete(`/ai/providers/${id}`);
  },

  testProvider(id) {
    return api.post(`/ai/providers/${id}/test`);
  },

  prompts(params = {}) {
    return api.get('/ai/prompts', { params });
  },

  showPrompt(id) {
    return api.get(`/ai/prompts/${id}`);
  },

  createPrompt(payload) {
    return api.post('/ai/prompts', payload);
  },

  updatePrompt(id, payload) {
    return api.put(`/ai/prompts/${id}`, payload);
  },

  deletePrompt(id) {
    return api.delete(`/ai/prompts/${id}`);
  },

  publishPrompt(id) {
    return api.post(`/ai/prompts/${id}/publish`);
  },

  conversations(params = {}) {
    return api.get('/ai/conversations', { params });
  },

  showConversation(id) {
    return api.get(`/ai/conversations/${id}`);
  },

  archiveConversation(id) {
    return api.post(`/ai/conversations/${id}/archive`);
  },

  chat(payload) {
    return api.post('/ai/chat', payload);
  },

  logs(params = {}) {
    return api.get('/ai/logs', { params });
  },

  showLog(id) {
    return api.get(`/ai/logs/${id}`);
  },

  feature(name, payload) {
    return api.post(`/ai/features/${name}`, payload);
  },
};
