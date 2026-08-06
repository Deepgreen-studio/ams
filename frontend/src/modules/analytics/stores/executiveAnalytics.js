import { defineStore } from 'pinia';
import { ref } from 'vue';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const useExecutiveAnalyticsStore = defineStore('executiveAnalytics', () => {
  const dashboard = ref(null);
  const scorecards = ref(null);
  const trends = ref(null);
  const forecast = ref(null);
  const widgets = ref(null);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  async function fetchDashboard(type = 'ceo', params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } =
        type === 'ceo' || type === 'overview'
          ? await analyticsService.executiveOverview(params)
          : await analyticsService.executiveDashboard(type, params);
      dashboard.value = data.data ?? null;
      return dashboard.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load executive dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchScorecards(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.executiveScorecards(params);
      scorecards.value = data.data ?? null;
      return scorecards.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load scorecards');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTrends(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.executiveTrends(params);
      trends.value = data.data ?? null;
      return trends.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load trends');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchForecast(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.executiveForecast(params);
      forecast.value = data.data ?? null;
      return forecast.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load forecast');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchWidgets(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.executiveWidgets(params);
      widgets.value = data.data ?? null;
      return widgets.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load widgets');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function capture(payload = {}) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.executiveCapture(payload);
      successMessage.value = data.message || 'Snapshot captured.';
      return data.data;
    } catch (err) {
      error.value = extractError(err, 'Unable to capture snapshot');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  return {
    dashboard,
    scorecards,
    trends,
    forecast,
    widgets,
    loading,
    saving,
    error,
    successMessage,
    fetchDashboard,
    fetchScorecards,
    fetchTrends,
    fetchForecast,
    fetchWidgets,
    capture,
  };
});
