import api from '@/services/api';

export const roleService = {
  list(params = {}) {
    return api.get('/roles', { params });
  },

  get(id) {
    return api.get(`/roles/${id}`);
  },

  create(payload) {
    return api.post('/roles', payload);
  },

  update(id, payload) {
    return api.put(`/roles/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/roles/${id}`);
  },

  restore(id) {
    return api.post(`/roles/${id}/restore`);
  },

  syncPermissions(id, permissions) {
    return api.post(`/roles/${id}/permissions`, { permissions });
  },

  assignUserRoles(userId, roles) {
    return api.post(`/users/${userId}/roles`, { roles });
  },

  removeUserRole(userId, roleId) {
    return api.delete(`/users/${userId}/roles/${roleId}`);
  },
};
