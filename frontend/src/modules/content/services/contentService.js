import api from '@/services/api';

export const contentService = {
  dashboard() {
    return api.get('/content/dashboard');
  },

  list(params = {}) {
    return api.get('/content', { params });
  },

  get(id) {
    return api.get(`/content/${id}`);
  },

  create(payload) {
    return api.post('/content', payload);
  },

  update(id, payload) {
    return api.put(`/content/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/content/${id}`);
  },

  restore(id) {
    return api.post(`/content/${id}/restore`);
  },

  publish(id, payload = {}) {
    return api.post(`/content/${id}/publish`, payload);
  },

  unpublish(id) {
    return api.post(`/content/${id}/unpublish`);
  },

  autosave(id, payload = {}) {
    return api.post(`/content/${id}/autosave`, payload);
  },

  uploadMedia(formData) {
    return api.post('/content/media', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  types() {
    return api.get('/content/types');
  },

  statuses() {
    return api.get('/content/statuses');
  },

  categories(params = {}) {
    return api.get('/content/categories', { params });
  },

  categoryTree(params = {}) {
    return api.get('/content/categories/tree', { params });
  },

  getCategory(id) {
    return api.get(`/content/categories/${id}`);
  },

  createCategory(payload) {
    return api.post('/content/categories', payload);
  },

  updateCategory(id, payload) {
    return api.put(`/content/categories/${id}`, payload);
  },

  removeCategory(id) {
    return api.delete(`/content/categories/${id}`);
  },

  restoreCategory(id) {
    return api.post(`/content/categories/${id}/restore`);
  },

  bulkCategories(payload) {
    return api.post('/content/categories/bulk', payload);
  },

  tags(params = {}) {
    return api.get('/content/tags', { params });
  },

  getTag(id) {
    return api.get(`/content/tags/${id}`);
  },

  createTag(payload) {
    return api.post('/content/tags', payload);
  },

  updateTag(id, payload) {
    return api.put(`/content/tags/${id}`, payload);
  },

  removeTag(id) {
    return api.delete(`/content/tags/${id}`);
  },

  restoreTag(id) {
    return api.post(`/content/tags/${id}/restore`);
  },

  bulkTags(payload) {
    return api.post('/content/tags/bulk', payload);
  },

  versions(id) {
    return api.get(`/content/${id}/versions`);
  },

  version(id, versionId) {
    return api.get(`/content/${id}/versions/${versionId}`);
  },

  compareVersions(id, from, to) {
    return api.get(`/content/${id}/versions/compare`, { params: { from, to } });
  },

  restoreVersion(id, versionId, payload = {}) {
    return api.post(`/content/${id}/versions/${versionId}/restore`, payload);
  },

  workflowQueue(params = {}) {
    return api.get('/content/workflow/queue', { params });
  },

  workflowHistory(id) {
    return api.get(`/content/${id}/workflow/history`);
  },

  submitForReview(id, payload = {}) {
    return api.post(`/content/${id}/workflow/submit`, payload);
  },

  markReviewed(id, payload = {}) {
    return api.post(`/content/${id}/workflow/review`, payload);
  },

  approveContent(id, payload = {}) {
    return api.post(`/content/${id}/workflow/approve`, payload);
  },

  rejectContent(id, payload = {}) {
    return api.post(`/content/${id}/workflow/reject`, payload);
  },

  publishWorkflow(id, payload = {}) {
    return api.post(`/content/${id}/workflow/publish`, payload);
  },

  archiveContent(id, payload = {}) {
    return api.post(`/content/${id}/workflow/archive`, payload);
  },

  returnToDraft(id, payload = {}) {
    return api.post(`/content/${id}/workflow/return-to-draft`, payload);
  },

  cmsRequest(path, { apiKey, params } = {}) {
    const headers = {};
    if (apiKey) {
      headers['X-CMS-Api-Key'] = apiKey;
    }

    const normalized = path.startsWith('/') ? path.slice(1) : path;

    return api.get(normalized, { headers, params });
  },

  cmsPublicContent(id, params = {}) {
    return api.get(`/cms/public/contents/${id}`, { params });
  },

  cmsPublicSeo(id, params = {}) {
    return api.get(`/cms/public/contents/${id}/seo`, { params });
  },

  cmsPublicSearch(params = {}) {
    return api.get('/cms/public/search', { params });
  },

  cmsPublicFeatured(params = {}) {
    return api.get('/cms/public/featured', { params });
  },

  cmsPublicLatest(params = {}) {
    return api.get('/cms/public/latest', { params });
  },

  cmsPublicPopular(params = {}) {
    return api.get('/cms/public/popular', { params });
  },

  cmsPrivatePreview(id, params = {}) {
    return api.get(`/cms/private/contents/${id}/preview`, { params });
  },

  cmsSitemapJson() {
    return api.get('/cms/seo/sitemap.json');
  },

  cmsRobotsJson() {
    return api.get('/cms/seo/robots.json');
  },

  listCmsApiKeys(params = {}) {
    return api.get('/content/api-keys', { params });
  },

  createCmsApiKey(payload) {
    return api.post('/content/api-keys', payload);
  },

  revokeCmsApiKey(uuid) {
    return api.delete(`/content/api-keys/${uuid}`);
  },
};
