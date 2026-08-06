import { defineStore } from 'pinia';
import { cannedResponseService } from '@/modules/support/services/cannedResponseService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const useCannedResponsesStore = defineStore('supportCannedResponses', {
  state: () => ({
    items: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
    statistics: null,
    recent: [],
    current: null,
    loading: false,
    saving: false,
    error: null,
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await cannedResponseService.dashboard();
        this.statistics = data.data.statistics;
        this.recent = data.data.recent || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load canned responses dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchList(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await cannedResponseService.list(params);
        this.items = data.data.responses.items || [];
        this.meta = data.data.responses.meta || this.meta;
        return data.data.responses;
      } catch (error) {
        this.error = extractError(error, 'Unable to load canned responses');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async create(payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await cannedResponseService.create(payload);
        return data.data.response;
      } catch (error) {
        this.error = extractError(error, 'Unable to create canned response');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async update(id, payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await cannedResponseService.update(id, payload);
        return data.data.response;
      } catch (error) {
        this.error = extractError(error, 'Unable to update canned response');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async remove(id) {
      this.saving = true;
      this.error = null;
      try {
        await cannedResponseService.remove(id);
      } catch (error) {
        this.error = extractError(error, 'Unable to delete canned response');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async use(id) {
      const { data } = await cannedResponseService.use(id);
      return data.data.response;
    },
  },
});
