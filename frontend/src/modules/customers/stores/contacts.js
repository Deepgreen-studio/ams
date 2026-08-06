import { defineStore } from 'pinia';
import { ref } from 'vue';
import { customerContactService } from '@/modules/customers/services/customerContactService';

const defaultFilters = () => ({
  search: '',
  status: '',
  contact_type: '',
  customer: '',
  department: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useCustomerContactsStore = defineStore('customerContacts', () => {
  const contacts = ref([]);
  const meta = ref(null);
  const currentContact = ref(null);
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

  async function fetchContacts(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await customerContactService.list(params);
      contacts.value = data.data?.contacts?.items ?? [];
      meta.value = data.data?.contacts?.meta ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load contacts');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchContact(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await customerContactService.get(id);
      currentContact.value = data.data?.contact ?? null;
      return currentContact.value;
    } catch (err) {
      applyError(err, 'Unable to load contact');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(id, limit = 50) {
    try {
      const { data } = await customerContactService.timeline(id, { limit });
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load contact timeline');
      throw err;
    }
  }

  async function createContact(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerContactService.create(payload);
      successMessage.value = data.message || 'Customer contact created successfully.';
      return data.data?.contact;
    } catch (err) {
      applyError(err, 'Unable to create contact');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateContact(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerContactService.update(id, payload);
      currentContact.value = data.data?.contact ?? currentContact.value;
      successMessage.value = data.message || 'Customer contact updated successfully.';
      return data.data?.contact;
    } catch (err) {
      applyError(err, 'Unable to update contact');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveContact(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerContactService.remove(id);
      successMessage.value = data.message || 'Customer contact archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive contact');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreContact(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerContactService.restore(id);
      currentContact.value = data.data?.contact ?? currentContact.value;
      successMessage.value = data.message || 'Customer contact restored successfully.';
      return data.data?.contact;
    } catch (err) {
      applyError(err, 'Unable to restore contact');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters(customer = '') {
    filters.value = { ...defaultFilters(), customer };
  }

  return {
    contacts,
    meta,
    currentContact,
    timeline,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    fetchContacts,
    fetchContact,
    fetchTimeline,
    createContact,
    updateContact,
    archiveContact,
    restoreContact,
    resetFilters,
    clearMessages,
  };
});
