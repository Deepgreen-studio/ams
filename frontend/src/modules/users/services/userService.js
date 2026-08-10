import api from '@/services/api';

export const userService = {
  list(params = {}) {
    return api.get('/users', { params });
  },

  get(id) {
    return api.get(`/users/${id}`);
  },

  create(payload) {
    return api.post('/users', payload);
  },

  update(id, payload) {
    return api.put(`/users/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/users/${id}`);
  },

  restore(id) {
    return api.post(`/users/${id}/restore`);
  },

  forceDelete(id) {
    return api.delete(`/users/${id}/force-delete`);
  },

  profile() {
    return api.get('/users/profile');
  },

  updateProfile(payload) {
    return api.put('/users/profile', payload);
  },

  uploadAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);

    // Let the browser set multipart boundary (do not force Content-Type).
    return api.post('/users/avatar', formData);
  },
};
