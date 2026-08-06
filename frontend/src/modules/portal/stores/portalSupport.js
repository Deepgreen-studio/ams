import { defineStore } from 'pinia';
import { portalSupportService } from '@/modules/portal/services/portalSupportService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const usePortalSupportStore = defineStore('portalSupport', {
  state: () => ({
    profile: null,
    tickets: [],
    meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    current: null,
    messages: [],
    loading: false,
    saving: false,
    error: null,
  }),

  actions: {
    async fetchProfile() {
      const { data } = await portalSupportService.profile();
      this.profile = data.data;
      return this.profile;
    },

    async fetchTickets(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await portalSupportService.tickets(params);
        this.tickets = data.data.tickets.items || [];
        this.meta = data.data.tickets.meta || this.meta;
        return data.data.tickets;
      } catch (error) {
        this.error = extractError(error, 'Unable to load tickets');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchTicket(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await portalSupportService.getTicket(id);
        this.current = data.data.ticket;
        return this.current;
      } catch (error) {
        this.error = extractError(error, 'Unable to load ticket');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createTicket(payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await portalSupportService.createTicket(payload);
        return data.data.ticket;
      } catch (error) {
        this.error = extractError(error, 'Unable to submit ticket');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchMessages(id) {
      const { data } = await portalSupportService.messages(id);
      this.messages = data.data.messages || [];
      return this.messages;
    },

    async reply(id, formData) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await portalSupportService.reply(id, formData);
        await this.fetchMessages(id);
        return data.data.message;
      } catch (error) {
        this.error = extractError(error, 'Unable to send reply');
        throw error;
      } finally {
        this.saving = false;
      }
    },
  },
});
