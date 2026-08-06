import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { customerService } from '@/modules/customers/services/customerService';

const defaultFilters = () => ({
  search: '',
  status: '',
  customer_type: '',
  company: '',
  country: '',
  industry: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useCustomersStore = defineStore('customers', () => {
  const customers = ref([]);
  const meta = ref(null);
  const statistics = ref(null);
  const currentCustomer = ref(null);
  const filters = ref(defaultFilters());
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  const totalCustomers = computed(() => meta.value?.total ?? 0);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  async function fetchCustomers(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );

      const { data } = await customerService.list(params);
      customers.value = data.data?.customers?.items ?? [];
      meta.value = data.data?.customers?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load customers');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCustomer(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await customerService.get(id);
      currentCustomer.value = data.data?.customer ?? null;
      return currentCustomer.value;
    } catch (err) {
      applyError(err, 'Unable to load customer');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createCustomer(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerService.create(payload);
      successMessage.value = data.message || 'Customer created successfully.';
      return data.data?.customer;
    } catch (err) {
      applyError(err, 'Unable to create customer');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateCustomer(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerService.update(id, payload);
      currentCustomer.value = data.data?.customer ?? currentCustomer.value;
      successMessage.value = data.message || 'Customer updated successfully.';
      return data.data?.customer;
    } catch (err) {
      applyError(err, 'Unable to update customer');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveCustomer(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerService.remove(id);
      successMessage.value = data.message || 'Customer archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive customer');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreCustomer(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerService.restore(id);
      currentCustomer.value = data.data?.customer ?? currentCustomer.value;
      successMessage.value = data.message || 'Customer restored successfully.';
      return data.data?.customer;
    } catch (err) {
      applyError(err, 'Unable to restore customer');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters() {
    filters.value = defaultFilters();
  }

  return {
    customers,
    meta,
    statistics,
    currentCustomer,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    totalCustomers,
    fetchCustomers,
    fetchCustomer,
    createCustomer,
    updateCustomer,
    archiveCustomer,
    restoreCustomer,
    resetFilters,
    clearMessages,
  };
});
