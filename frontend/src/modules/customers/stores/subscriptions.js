import { defineStore } from 'pinia';
import { ref } from 'vue';
import { subscriptionService } from '@/modules/customers/services/subscriptionService';

const defaultFilters = () => ({
  search: '',
  status: '',
  plan_type: '',
  payment_status: '',
  customer: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useSubscriptionsStore = defineStore('customerSubscriptions', () => {
  const subscriptions = ref([]);
  const renewalReminders = ref([]);
  const statistics = ref(null);
  const meta = ref(null);
  const currentSubscription = ref(null);
  const timeline = ref([]);
  const filters = ref(defaultFilters());
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

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  async function fetchDashboard(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await subscriptionService.dashboard(params);
      subscriptions.value = data.data?.subscriptions?.items ?? [];
      meta.value = data.data?.subscriptions?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      renewalReminders.value = data.data?.renewal_reminders ?? [];
      return data;
    } catch (err) {
      applyError(err, 'Unable to load subscription dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSubscriptions(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await subscriptionService.list(params);
      subscriptions.value = data.data?.subscriptions?.items ?? [];
      meta.value = data.data?.subscriptions?.meta ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load subscriptions');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSubscription(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await subscriptionService.get(id);
      currentSubscription.value = data.data?.subscription ?? null;
      return currentSubscription.value;
    } catch (err) {
      applyError(err, 'Unable to load subscription');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(id, limit = 50) {
    try {
      const { data } = await subscriptionService.timeline(id, { limit });
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load subscription timeline');
      throw err;
    }
  }

  async function createSubscription(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await subscriptionService.create(payload);
      successMessage.value = data.message || 'Subscription created successfully.';
      return data.data?.subscription;
    } catch (err) {
      applyError(err, 'Unable to create subscription');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateSubscription(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await subscriptionService.update(id, payload);
      currentSubscription.value = data.data?.subscription ?? currentSubscription.value;
      successMessage.value = data.message || 'Subscription updated successfully.';
      return data.data?.subscription;
    } catch (err) {
      applyError(err, 'Unable to update subscription');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function cancelSubscription(id, reason = '') {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await subscriptionService.cancel(id, { reason: reason || null });
      currentSubscription.value = data.data?.subscription ?? currentSubscription.value;
      successMessage.value = data.message || 'Subscription cancelled successfully.';
      return data.data?.subscription;
    } catch (err) {
      applyError(err, 'Unable to cancel subscription');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveSubscription(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await subscriptionService.remove(id);
      successMessage.value = data.message || 'Subscription archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive subscription');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreSubscription(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await subscriptionService.restore(id);
      currentSubscription.value = data.data?.subscription ?? currentSubscription.value;
      successMessage.value = data.message || 'Subscription restored successfully.';
      return data.data?.subscription;
    } catch (err) {
      applyError(err, 'Unable to restore subscription');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters(customer = '') {
    filters.value = { ...defaultFilters(), customer };
  }

  return {
    subscriptions,
    renewalReminders,
    statistics,
    meta,
    currentSubscription,
    timeline,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    fetchDashboard,
    fetchSubscriptions,
    fetchSubscription,
    fetchTimeline,
    createSubscription,
    updateSubscription,
    cancelSubscription,
    archiveSubscription,
    restoreSubscription,
    resetFilters,
    clearMessages,
  };
});
