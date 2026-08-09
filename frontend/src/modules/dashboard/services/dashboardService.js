import api from '@/services/api';

export const dashboardService = {
  overview(params = {}) {
    return api.get('/dashboard', { params });
  },
};
