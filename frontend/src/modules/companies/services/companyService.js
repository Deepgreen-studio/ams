import api from '@/services/api';

export const companyService = {
  list(params = {}) {
    return api.get('/companies', { params });
  },
  get(id) {
    return api.get(`/companies/${id}`);
  },
  create(payload) {
    return api.post('/companies', payload);
  },
  update(id, payload) {
    return api.put(`/companies/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/companies/${id}`);
  },
  restore(id) {
    return api.post(`/companies/${id}/restore`);
  },
  uploadLogo(id, file) {
    const formData = new FormData();
    formData.append('file', file);
    return api.post(`/companies/${id}/logo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  uploadFavicon(id, file) {
    const formData = new FormData();
    formData.append('file', file);
    return api.post(`/companies/${id}/favicon`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  updateBranding(id, payload) {
    return api.put(`/companies/${id}/branding`, payload);
  },
  listDepartments(params = {}) {
    return api.get('/departments', { params });
  },
  createDepartment(payload) {
    return api.post('/departments', payload);
  },
  updateDepartment(id, payload) {
    return api.put(`/departments/${id}`, payload);
  },
  deleteDepartment(id) {
    return api.delete(`/departments/${id}`);
  },
  listTeams(params = {}) {
    return api.get('/teams', { params });
  },
  createTeam(payload) {
    return api.post('/teams', payload);
  },
  updateTeam(id, payload) {
    return api.put(`/teams/${id}`, payload);
  },
  deleteTeam(id) {
    return api.delete(`/teams/${id}`);
  },
  listLocations(params = {}) {
    return api.get('/company-locations', { params });
  },
  createLocation(payload) {
    return api.post('/company-locations', payload);
  },
  updateLocation(id, payload) {
    return api.put(`/company-locations/${id}`, payload);
  },
  deleteLocation(id) {
    return api.delete(`/company-locations/${id}`);
  },
};
