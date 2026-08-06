import api from '@/services/api';

export const permissionService = {
  list(params = {}) {
    return api.get('/permissions', { params });
  },

  groups() {
    return api.get('/permissions/groups');
  },

  matrix(role = null) {
    return api.get('/permissions/matrix', {
      params: role ? { role } : {},
    });
  },
};
