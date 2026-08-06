import { defineStore } from 'pinia';
import { automationService } from '@/modules/automation/services/automationService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

const defaultMeta = { current_page: 1, last_page: 1, per_page: 20, total: 0 };

export const useAutomationStore = defineStore('automation', {
  state: () => ({
    rules: [],
    ruleMeta: { ...defaultMeta },
    currentRule: null,
    catalog: {
      trigger_types: [],
      events: [],
      operators: [],
      actions: [],
    },
    logs: [],
    logMeta: { ...defaultMeta },
    logStatistics: null,
    statistics: null,
    loading: false,
    saving: false,
    error: null,
    successMessage: null,
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await automationService.dashboard();
        this.statistics = data.data.statistics || null;
        this.logStatistics = data.data.log_statistics || null;
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load automation dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCatalog() {
      try {
        const { data } = await automationService.catalog();
        this.catalog = data.data.catalog || this.catalog;
        return this.catalog;
      } catch (error) {
        this.error = extractError(error, 'Unable to load automation catalog');
        throw error;
      }
    },

    async fetchRules(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await automationService.list(params);
        this.rules = data.data.rules?.items || [];
        this.ruleMeta = data.data.rules?.meta || { ...defaultMeta };
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load automation rules');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchRule(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await automationService.show(id);
        this.currentRule = data.data.rule;
        this.catalog = data.data.catalog || this.catalog;
        return this.currentRule;
      } catch (error) {
        this.error = extractError(error, 'Unable to load automation rule');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async saveRule(payload, id = null) {
      this.saving = true;
      this.error = null;
      this.successMessage = null;
      try {
        const { data } = id
          ? await automationService.update(id, payload)
          : await automationService.create(payload);
        this.currentRule = data.data.rule;
        this.successMessage = data.message || 'Rule saved.';
        return this.currentRule;
      } catch (error) {
        this.error = extractError(error, 'Unable to save automation rule');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async toggleRule(id, isEnabled) {
      this.error = null;
      try {
        const { data } = await automationService.toggle(id, isEnabled);
        const rule = data.data.rule;
        const index = this.rules.findIndex((item) => item.uuid === id);
        if (index >= 0) {
          this.rules[index] = rule;
        }
        if (this.currentRule?.uuid === id) {
          this.currentRule = rule;
        }
        this.successMessage = data.message;
        return rule;
      } catch (error) {
        this.error = extractError(error, 'Unable to toggle rule');
        throw error;
      }
    },

    async deleteRule(id) {
      this.error = null;
      try {
        await automationService.remove(id);
        this.rules = this.rules.filter((item) => item.uuid !== id);
        this.successMessage = 'Automation rule deleted.';
      } catch (error) {
        this.error = extractError(error, 'Unable to delete rule');
        throw error;
      }
    },

    async fetchLogs(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await automationService.logs(params);
        this.logs = data.data.logs?.items || [];
        this.logMeta = data.data.logs?.meta || { ...defaultMeta };
        this.logStatistics = data.data.statistics || this.logStatistics;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load automation logs');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async testRule(id, context = {}) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await automationService.test(id, context);
        this.successMessage = data.message || 'Test run completed.';
        return data.data.result;
      } catch (error) {
        this.error = extractError(error, 'Unable to test rule');
        throw error;
      } finally {
        this.saving = false;
      }
    },
  },
});
