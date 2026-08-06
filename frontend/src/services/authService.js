import api, { ensureCsrfCookie } from './api';

export const authService = {
  async login(payload) {
    await ensureCsrfCookie();
    return api.post('/auth/login', payload);
  },

  async logout() {
    return api.post('/auth/logout');
  },

  async me() {
    return api.get('/auth/me');
  },

  async forgotPassword(payload) {
    await ensureCsrfCookie();
    return api.post('/auth/forgot-password', payload);
  },

  async resetPassword(payload) {
    await ensureCsrfCookie();
    return api.post('/auth/reset-password', payload);
  },
};
