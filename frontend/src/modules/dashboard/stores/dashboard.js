import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { dashboardService } from '@/modules/dashboard/services/dashboardService';

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const days = ref(30);

  const metrics = computed(() => data.value?.metrics ?? []);
  const applicationSummary = computed(() => data.value?.application_summary?.items ?? []);
  const overallProgress = computed(() => data.value?.overall_progress ?? null);
  const todaysTasks = computed(() => data.value?.todays_tasks ?? null);
  const teamWorkload = computed(() => data.value?.team_workload ?? null);
  const period = computed(() => data.value?.period ?? null);

  function clearMessages() {
    error.value = null;
  }

  async function fetchOverview(overrides = {}) {
    loading.value = true;
    clearMessages();

    if (overrides.days != null) {
      days.value = Number(overrides.days) || 30;
    }

    try {
      const { data: payload } = await dashboardService.overview({
        days: days.value,
        ...overrides,
      });
      data.value = payload?.data ?? null;
      return data.value;
    } catch (err) {
      error.value = err?.message || 'Unable to load dashboard';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    data,
    loading,
    error,
    days,
    metrics,
    applicationSummary,
    overallProgress,
    todaysTasks,
    teamWorkload,
    period,
    fetchOverview,
  };
});
