import api from '@/services/api';

export const knowledgeBaseService = {
  dashboard() {
    return api.get('/support/knowledge/dashboard');
  },

  articles(params = {}) {
    return api.get('/support/knowledge/articles', { params });
  },

  getArticle(id) {
    return api.get(`/support/knowledge/articles/${id}`);
  },

  createArticle(payload) {
    return api.post('/support/knowledge/articles', payload);
  },

  updateArticle(id, payload) {
    return api.put(`/support/knowledge/articles/${id}`, payload);
  },

  publishArticle(id) {
    return api.post(`/support/knowledge/articles/${id}/publish`);
  },

  archiveArticle(id) {
    return api.post(`/support/knowledge/articles/${id}/archive`);
  },

  deleteArticle(id) {
    return api.delete(`/support/knowledge/articles/${id}`);
  },

  linkCms(id, payload) {
    return api.post(`/support/knowledge/articles/${id}/link-cms`, payload);
  },

  unlinkCms(id) {
    return api.post(`/support/knowledge/articles/${id}/unlink-cms`);
  },

  versions(id) {
    return api.get(`/support/knowledge/articles/${id}/versions`);
  },

  restoreVersion(id, versionId) {
    return api.post(`/support/knowledge/articles/${id}/versions/${versionId}/restore`);
  },

  feedback(id, payload) {
    return api.post(`/support/knowledge/articles/${id}/feedback`, payload);
  },

  categories(params = {}) {
    return api.get('/support/knowledge/categories', { params });
  },

  createCategory(payload) {
    return api.post('/support/knowledge/categories', payload);
  },

  tags() {
    return api.get('/support/knowledge/tags');
  },

  createTag(payload) {
    return api.post('/support/knowledge/tags', payload);
  },
};
