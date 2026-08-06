import { defineStore } from 'pinia';
import { workflowService } from '@/modules/workflows/services/workflowService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

const defaultMeta = { current_page: 1, last_page: 1, per_page: 20, total: 0 };

export const useWorkflowStore = defineStore('workflows', {
  state: () => ({
    workflows: [],
    workflowMeta: { ...defaultMeta },
    currentWorkflow: null,
    catalog: { types: [], statuses: [], step_types: [] },
    statistics: null,
    instanceStatistics: null,
    instances: [],
    instanceMeta: { ...defaultMeta },
    currentInstance: null,
    queue: [],
    queueMeta: { ...defaultMeta },
    logs: [],
    logMeta: { ...defaultMeta },
    monitorRecent: [],
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
        const { data } = await workflowService.dashboard();
        this.statistics = data.data.statistics || null;
        this.instanceStatistics = data.data.instance_statistics || null;
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load workflow dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCatalog() {
      const { data } = await workflowService.catalog();
      this.catalog = data.data.catalog || this.catalog;
      return this.catalog;
    },

    async fetchWorkflows(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await workflowService.list(params);
        this.workflows = data.data.workflows?.items || [];
        this.workflowMeta = data.data.workflows?.meta || { ...defaultMeta };
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load workflows');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchWorkflow(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await workflowService.show(id);
        this.currentWorkflow = data.data.workflow;
        this.catalog = data.data.catalog || this.catalog;
        return this.currentWorkflow;
      } catch (error) {
        this.error = extractError(error, 'Unable to load workflow');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async saveWorkflow(payload, id = null) {
      this.saving = true;
      this.error = null;
      this.successMessage = null;
      try {
        const { data } = id
          ? await workflowService.update(id, payload)
          : await workflowService.create(payload);
        this.currentWorkflow = data.data.workflow;
        this.successMessage = data.message || 'Workflow saved.';
        return this.currentWorkflow;
      } catch (error) {
        this.error = extractError(error, 'Unable to save workflow');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async toggleWorkflow(id, isEnabled) {
      const { data } = await workflowService.toggle(id, isEnabled);
      const workflow = data.data.workflow;
      const index = this.workflows.findIndex((item) => item.uuid === id);
      if (index >= 0) this.workflows[index] = workflow;
      if (this.currentWorkflow?.uuid === id) this.currentWorkflow = workflow;
      this.successMessage = data.message;
      return workflow;
    },

    async publishWorkflow(id) {
      const { data } = await workflowService.publish(id);
      this.currentWorkflow = data.data.workflow;
      this.successMessage = data.message;
      return this.currentWorkflow;
    },

    async deleteWorkflow(id) {
      await workflowService.remove(id);
      this.workflows = this.workflows.filter((item) => item.uuid !== id);
      this.successMessage = 'Workflow deleted.';
    },

    async startWorkflow(id, payload = {}) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await workflowService.start(id, payload);
        this.currentInstance = data.data.instance;
        this.successMessage = data.message || 'Workflow started.';
        return this.currentInstance;
      } catch (error) {
        this.error = extractError(error, 'Unable to start workflow');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchMonitor() {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await workflowService.monitor();
        this.instanceStatistics = data.data.statistics || null;
        this.monitorRecent = data.data.recent || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load monitor');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchQueue(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await workflowService.queue(params);
        this.queue = data.data.queue?.items || [];
        this.queueMeta = data.data.queue?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load approval queue');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchInstance(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await workflowService.instance(id);
        this.currentInstance = data.data.instance;
        return this.currentInstance;
      } catch (error) {
        this.error = extractError(error, 'Unable to load instance');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchHistory(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await workflowService.history(params);
        this.logs = data.data.logs?.items || [];
        this.logMeta = data.data.logs?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load history');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async approveInstance(id, comment = '') {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await workflowService.approve(id, comment);
        this.currentInstance = data.data.instance;
        this.queue = this.queue.filter((item) => item.uuid !== id);
        this.successMessage = data.message;
        return this.currentInstance;
      } catch (error) {
        this.error = extractError(error, 'Unable to approve');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async rejectInstance(id, comment = '') {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await workflowService.reject(id, comment);
        this.currentInstance = data.data.instance;
        this.queue = this.queue.filter((item) => item.uuid !== id);
        this.successMessage = data.message;
        return this.currentInstance;
      } catch (error) {
        this.error = extractError(error, 'Unable to reject');
        throw error;
      } finally {
        this.saving = false;
      }
    },
  },
});
