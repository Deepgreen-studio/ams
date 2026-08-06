import api from '@/services/api';

export const policyDocumentService = {
  dashboard(params = {}) {
    return api.get('/compliance/policies/dashboard', { params });
  },
  approvalQueue(params = {}) {
    return api.get('/compliance/policies/approvals', { params });
  },
  list(params = {}) {
    return api.get('/compliance/policies', { params });
  },
  get(id) {
    return api.get(`/compliance/policies/${id}`);
  },
  create(payload) {
    return api.post('/compliance/policies', payload);
  },
  update(id, payload) {
    return api.put(`/compliance/policies/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/compliance/policies/${id}`);
  },
  versions(id) {
    return api.get(`/compliance/policies/${id}/versions`);
  },
  showVersion(id, version) {
    return api.get(`/compliance/policies/${id}/versions/${version}`);
  },
  compare(id, from, to) {
    return api.get(`/compliance/policies/${id}/versions/compare`, { params: { from, to } });
  },
  restoreVersion(id, version, payload = {}) {
    return api.post(`/compliance/policies/${id}/versions/${version}/restore`, payload);
  },
  submit(id, payload = {}) {
    return api.post(`/compliance/policies/${id}/submit`, payload);
  },
  publish(id, payload = {}) {
    return api.post(`/compliance/policies/${id}/publish`, payload);
  },
  approve(approvalId, payload = {}) {
    return api.post(`/compliance/policies/approvals/${approvalId}/approve`, payload);
  },
  reject(approvalId, payload = {}) {
    return api.post(`/compliance/policies/approvals/${approvalId}/reject`, payload);
  },
  cmsVersions(id) {
    return api.get(`/compliance/policies/${id}/cms-versions`);
  },
  linkCms(id, payload) {
    return api.post(`/compliance/policies/${id}/link-cms`, payload);
  },
};
