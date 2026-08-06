import { defineStore } from 'pinia';
import { ref } from 'vue';
import { auditService } from '@/modules/audit/services/auditService';

function useAsyncState() {
  const loading = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
  }

  return { loading, error, successMessage, clearMessages, applyError };
}

function createListStore(loaderKey, responseKey) {
  return defineStore(loaderKey, () => {
    const items = ref([]);
    const meta = ref(null);
    const current = ref(null);
    const filters = ref({
      search: '',
      module: '',
      action: '',
      status: '',
      method: '',
      date_from: '',
      date_to: '',
      per_page: 15,
      page: 1,
    });
    const state = useAsyncState();

    async function fetchList(overrides = {}) {
      state.loading.value = true;
      state.clearMessages();
      filters.value = { ...filters.value, ...overrides };
      try {
        const params = Object.fromEntries(
          Object.entries(filters.value).filter(([, v]) => v !== '' && v != null),
        );
        const { data } = await auditService[loaderKey](params);
        items.value = data.data?.[responseKey]?.items ?? [];
        meta.value = data.data?.[responseKey]?.meta ?? null;
      } catch (err) {
        state.applyError(err, `Unable to load ${responseKey}`);
        throw err;
      } finally {
        state.loading.value = false;
      }
    }

    return { items, meta, current, filters, ...state, fetchList };
  });
}

export const useActivityStore = createListStore('activityLogs', 'activity_logs');
export const useAuditStore = createListStore('auditLogs', 'audit_logs');
export const useLoginHistoryStore = createListStore('loginHistory', 'login_history');
export const useSystemEventsStore = createListStore('systemEvents', 'system_events');
export const useApiLogsStore = createListStore('apiLogs', 'api_logs');
export const useErrorLogsStore = createListStore('errorLogs', 'error_logs');
