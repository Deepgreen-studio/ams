import { defineStore } from 'pinia';
import { ref } from 'vue';
import { mappingService } from '@/modules/mappings/services/mappingService';

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

export const useMappingsStore = defineStore('mappings', () => {
  const mappings = ref([]);
  const meta = ref(null);
  const currentMapping = ref(null);
  const catalogs = ref(null);
  const previewResult = ref(null);
  const validationResult = ref(null);
  const filters = ref({
    search: '',
    direction: '',
    status: '',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchCatalogs() {
    try {
      const { data } = await mappingService.catalogs();
      catalogs.value = data.data ?? null;
      return catalogs.value;
    } catch (err) {
      state.applyError(err, 'Unable to load mapping catalogs');
      throw err;
    }
  }

  async function fetchMappings(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null),
      );
      const { data } = await mappingService.list(params);
      mappings.value = data.data?.mappings?.items ?? [];
      meta.value = data.data?.mappings?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load data mappings');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchMapping(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await mappingService.get(id);
      currentMapping.value = data.data?.mapping ?? null;
      return currentMapping.value;
    } catch (err) {
      state.applyError(err, 'Unable to load data mapping');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createMapping(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mappingService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.mapping;
    } catch (err) {
      state.applyError(err, 'Unable to create data mapping');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateMapping(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mappingService.update(id, payload);
      currentMapping.value = data.data?.mapping ?? currentMapping.value;
      state.successMessage.value = data.message;
      return data.data?.mapping;
    } catch (err) {
      state.applyError(err, 'Unable to update data mapping');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteMapping(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mappingService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete data mapping');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function previewMapping(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mappingService.preview(id, payload);
      previewResult.value = data.data ?? null;
      return previewResult.value;
    } catch (err) {
      state.applyError(err, 'Unable to preview mapping');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function validateMapping(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await mappingService.validate(id, payload);
      validationResult.value = data.data ?? null;
      state.successMessage.value = data.message;
      return validationResult.value;
    } catch (err) {
      state.applyError(err, 'Unable to validate mapping');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    mappings,
    meta,
    currentMapping,
    catalogs,
    previewResult,
    validationResult,
    filters,
    ...state,
    fetchCatalogs,
    fetchMappings,
    fetchMapping,
    createMapping,
    updateMapping,
    deleteMapping,
    previewMapping,
    validateMapping,
  };
});
