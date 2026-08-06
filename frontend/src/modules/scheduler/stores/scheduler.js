import { defineStore } from 'pinia';
import { schedulerService } from '@/modules/scheduler/services/schedulerService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

const defaultMeta = { current_page: 1, last_page: 1, per_page: 20, total: 0 };

export const useSchedulerStore = defineStore('scheduler', {
  state: () => ({
    jobs: [],
    jobMeta: { ...defaultMeta },
    currentJob: null,
    catalog: { job_types: [], handlers: [], common_cron: {} },
    statistics: null,
    runStatistics: null,
    runs: [],
    runMeta: { ...defaultMeta },
    recentRuns: [],
    recentFailed: [],
    logs: [],
    logMeta: { ...defaultMeta },
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
        const { data } = await schedulerService.dashboard();
        this.statistics = data.data.statistics || null;
        this.runStatistics = data.data.run_statistics || null;
        this.catalog = data.data.catalog || this.catalog;
        this.recentRuns = data.data.recent_runs || [];
        this.recentFailed = data.data.recent_failed || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load scheduler dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCatalog() {
      const { data } = await schedulerService.catalog();
      this.catalog = data.data.catalog || this.catalog;
      return this.catalog;
    },

    async fetchJobs(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await schedulerService.list(params);
        this.jobs = data.data.jobs?.items || [];
        this.jobMeta = data.data.jobs?.meta || { ...defaultMeta };
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load scheduled jobs');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchJob(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await schedulerService.show(id);
        this.currentJob = data.data.job;
        this.catalog = data.data.catalog || this.catalog;
        return this.currentJob;
      } catch (error) {
        this.error = extractError(error, 'Unable to load job');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async saveJob(payload, id = null) {
      this.saving = true;
      this.error = null;
      this.successMessage = null;
      try {
        const { data } = id
          ? await schedulerService.update(id, payload)
          : await schedulerService.create(payload);
        this.currentJob = data.data.job;
        this.successMessage = data.message || 'Job saved.';
        return this.currentJob;
      } catch (error) {
        this.error = extractError(error, 'Unable to save job');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async toggleJob(id, isEnabled) {
      const { data } = await schedulerService.toggle(id, isEnabled);
      const job = data.data.job;
      const index = this.jobs.findIndex((item) => item.uuid === id);
      if (index >= 0) this.jobs[index] = job;
      this.successMessage = data.message;
      return job;
    },

    async deleteJob(id) {
      await schedulerService.remove(id);
      this.jobs = this.jobs.filter((item) => item.uuid !== id);
      this.successMessage = 'Scheduled job deleted.';
    },

    async runJob(id) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await schedulerService.runNow(id);
        this.successMessage = data.message || 'Job dispatched.';
        return data.data.result;
      } catch (error) {
        this.error = extractError(error, 'Unable to run job');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchHistory(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await schedulerService.history(params);
        this.runs = data.data.runs?.items || [];
        this.runMeta = data.data.runs?.meta || { ...defaultMeta };
        this.runStatistics = data.data.statistics || this.runStatistics;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load history');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchRunning(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await schedulerService.running(params);
        this.runs = data.data.runs?.items || [];
        this.runMeta = data.data.runs?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load running jobs');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchFailed(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await schedulerService.failed(params);
        this.runs = data.data.runs?.items || [];
        this.runMeta = data.data.runs?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load failed jobs');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async retryRun(id) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await schedulerService.retry(id);
        this.successMessage = data.message || 'Retry dispatched.';
        this.runs = this.runs.filter((item) => item.uuid !== id);
        return data.data.result;
      } catch (error) {
        this.error = extractError(error, 'Unable to retry job');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchLogs(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await schedulerService.logs(params);
        this.logs = data.data.logs?.items || [];
        this.logMeta = data.data.logs?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load logs');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchStatistics() {
      const { data } = await schedulerService.statistics();
      this.statistics = data.data.jobs || null;
      this.runStatistics = data.data.runs || null;
      return data.data;
    },
  },
});
