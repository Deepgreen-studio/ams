import { defineStore } from 'pinia';
import { ref } from 'vue';
import { queueService } from '@/modules/queue/services/queueService';

function useAsyncState() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
  }

  return { loading, saving, error, successMessage, clearMessages, applyError };
}

export const useQueueStore = defineStore('queue', () => {
  const dashboard = ref(null);
  const statistics = ref(null);
  const tracks = ref([]);
  const tracksMeta = ref(null);
  const failed = ref([]);
  const failedMeta = ref(null);
  const pending = ref([]);
  const pendingMeta = ref(null);
  const state = useAsyncState();

  async function fetchDashboard() {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.dashboard();
      dashboard.value = data.data ?? null;
      return dashboard.value;
    } catch (err) {
      state.applyError(err, 'Unable to load queue dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchStatistics() {
    state.loading.value = true;
    try {
      const { data } = await queueService.statistics();
      statistics.value = data.data ?? null;
      return statistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load queue statistics');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTracks(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await queueService.tracks(params);
      tracks.value = data.data?.tracks?.items ?? [];
      tracksMeta.value = data.data?.tracks?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load job tracks');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRunning(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await queueService.running(params);
      tracks.value = data.data?.tracks?.items ?? [];
      tracksMeta.value = data.data?.tracks?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load running jobs');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchFailed(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await queueService.failed(params);
      failed.value = data.data?.failed?.items ?? [];
      failedMeta.value = data.data?.failed?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load failed jobs');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchPending(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await queueService.pending(params);
      pending.value = data.data?.jobs?.items ?? [];
      pendingMeta.value = data.data?.jobs?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load pending jobs');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function retryFailed(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.retryFailed(id);
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to retry failed job');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function retryAllFailed() {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.retryAllFailed();
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to retry failed jobs');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function forgetFailed(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.forgetFailed(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to remove failed job');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function flushFailed() {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.flushFailed();
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to flush failed jobs');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function restartWorkers() {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.restart();
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to restart workers');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function dispatchSample(payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await queueService.dispatchSample(payload);
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to dispatch sample job');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    dashboard,
    statistics,
    tracks,
    tracksMeta,
    failed,
    failedMeta,
    pending,
    pendingMeta,
    ...state,
    fetchDashboard,
    fetchStatistics,
    fetchTracks,
    fetchRunning,
    fetchFailed,
    fetchPending,
    retryFailed,
    retryAllFailed,
    forgetFailed,
    flushFailed,
    restartWorkers,
    dispatchSample,
  };
});
