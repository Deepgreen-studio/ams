import { defineStore } from 'pinia';
import { ref } from 'vue';
import { webhookService } from '@/modules/webhooks/services/webhookService';

function useAsyncState() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  return { loading, saving, error, fieldErrors, successMessage, clearMessages, applyError };
}

export const useWebhooksStore = defineStore('webhooks', () => {
  const webhooks = ref([]);
  const meta = ref(null);
  const currentWebhook = ref(null);
  const logs = ref([]);
  const logsMeta = ref(null);
  const currentLog = ref(null);
  const events = ref([]);
  const eventsMeta = ref(null);
  const lastTestLog = ref(null);
  const filters = ref({
    search: '',
    status: '',
    direction: '',
    company: '',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchWebhooks(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '' && v != null));
      const { data } = await webhookService.list(params);
      webhooks.value = data.data?.webhooks?.items ?? [];
      meta.value = data.data?.webhooks?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load webhooks');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchWebhook(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.get(id);
      currentWebhook.value = data.data?.webhook ?? null;
      return currentWebhook.value;
    } catch (err) {
      state.applyError(err, 'Unable to load webhook');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createWebhook(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.webhook;
    } catch (err) {
      state.applyError(err, 'Unable to create webhook');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateWebhook(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.update(id, payload);
      currentWebhook.value = data.data?.webhook ?? currentWebhook.value;
      state.successMessage.value = data.message;
      return data.data?.webhook;
    } catch (err) {
      state.applyError(err, 'Unable to update webhook');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteWebhook(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete webhook');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function testWebhook(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.test(id, payload);
      lastTestLog.value = data.data?.log ?? null;
      currentWebhook.value = data.data?.webhook ?? currentWebhook.value;
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Webhook test failed');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchLogs(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.logs(params);
      logs.value = data.data?.logs?.items ?? [];
      logsMeta.value = data.data?.logs?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load webhook logs');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchLog(id) {
    state.loading.value = true;
    try {
      const { data } = await webhookService.log(id);
      currentLog.value = data.data?.log ?? null;
      return currentLog.value;
    } catch (err) {
      state.applyError(err, 'Unable to load webhook log');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function retryLog(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await webhookService.retry(id);
      lastTestLog.value = data.data?.log ?? null;
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to retry webhook');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchEvents(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await webhookService.events(params);
      events.value = data.data?.events?.items ?? [];
      eventsMeta.value = data.data?.events?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load webhook events');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  return {
    webhooks,
    meta,
    currentWebhook,
    logs,
    logsMeta,
    currentLog,
    events,
    eventsMeta,
    lastTestLog,
    filters,
    ...state,
    fetchWebhooks,
    fetchWebhook,
    createWebhook,
    updateWebhook,
    deleteWebhook,
    testWebhook,
    fetchLogs,
    fetchLog,
    retryLog,
    fetchEvents,
  };
});
