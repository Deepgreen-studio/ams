import api from '@/services/api';

export const notificationService = {
  dashboard() {
    return api.get('/notifications/dashboard');
  },

  center() {
    return api.get('/notifications/center');
  },

  unreadCount() {
    return api.get('/notifications/unread-count');
  },

  list(params = {}) {
    return api.get('/notifications', { params });
  },

  unread(params = {}) {
    return api.get('/notifications/unread', { params });
  },

  create(payload) {
    return api.post('/notifications', payload);
  },

  markRead(id) {
    return api.post(`/notifications/${id}/read`);
  },

  markAllRead() {
    return api.post('/notifications/read-all');
  },

  remove(id) {
    return api.delete(`/notifications/${id}`);
  },

  preferences() {
    return api.get('/notifications/preferences');
  },

  syncPreferences(preferences) {
    return api.put('/notifications/preferences', { preferences });
  },

  channels() {
    return api.get('/notifications/channels');
  },

  updateChannel(id, payload) {
    return api.put(`/notifications/channels/${id}`, payload);
  },

  templates(params = {}) {
    return api.get('/notifications/templates', { params });
  },

  template(id) {
    return api.get(`/notifications/templates/${id}`);
  },

  createTemplate(payload) {
    return api.post('/notifications/templates', payload);
  },

  updateTemplate(id, payload) {
    return api.put(`/notifications/templates/${id}`, payload);
  },

  deleteTemplate(id) {
    return api.delete(`/notifications/templates/${id}`);
  },

  previewTemplate(id, payload = {}) {
    return api.post(`/notifications/templates/${id}/preview`, payload);
  },

  testSendTemplate(id, payload = {}) {
    return api.post(`/notifications/templates/${id}/test-send`, payload);
  },

  submitTemplate(id, payload = {}) {
    return api.post(`/notifications/templates/${id}/submit`, payload);
  },

  publishTemplate(id) {
    return api.post(`/notifications/templates/${id}/publish`);
  },

  templateVersions(id) {
    return api.get(`/notifications/templates/${id}/versions`);
  },

  compareTemplateVersions(id, from, to) {
    return api.get(`/notifications/templates/${id}/versions/compare`, { params: { from, to } });
  },

  restoreTemplateVersion(id, version, payload = {}) {
    return api.post(`/notifications/templates/${id}/versions/${version}/restore`, payload);
  },

  templateApprovals(params = {}) {
    return api.get('/notifications/templates/approvals', { params });
  },

  approveTemplate(approvalId, payload = {}) {
    return api.post(`/notifications/templates/approvals/${approvalId}/approve`, payload);
  },

  rejectTemplate(approvalId, payload = {}) {
    return api.post(`/notifications/templates/approvals/${approvalId}/reject`, payload);
  },

  deliveryLogs(params = {}) {
    return api.get('/notifications/logs', { params });
  },
};
