import axios from 'axios';
import api, { ensureCsrfCookie } from '@/services/api';

export const authService = {
  async login(payload) {
    await ensureCsrfCookie();
    return api.post('/auth/login', payload);
  },

  async logout() {
    return api.post('/auth/logout');
  },

  async logoutAll() {
    return api.post('/auth/logout-all');
  },

  async me() {
    return api.get('/auth/me');
  },

  async refresh(payload = {}) {
    return api.post('/auth/refresh', payload);
  },

  async forgotPassword(payload) {
    await ensureCsrfCookie();
    return api.post('/auth/forgot-password', payload);
  },

  async resetPassword(payload) {
    await ensureCsrfCookie();
    return api.post('/auth/reset-password', payload);
  },

  async changePassword(payload) {
    return api.post('/auth/change-password', payload);
  },

  async sendVerificationEmail() {
    return api.post('/auth/email/verification-notification');
  },

  async verifyEmail(verifyUrl) {
    return axios.get(verifyUrl, {
      withCredentials: true,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  },
};
