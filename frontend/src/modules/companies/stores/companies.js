import { defineStore } from 'pinia';
import { ref } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';

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

export const useCompaniesStore = defineStore('companies', () => {
  const companies = ref([]);
  const meta = ref(null);
  const currentCompany = ref(null);
  const filters = ref({
    search: '',
    status: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchCompanies(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '' && v != null));
      const { data } = await companyService.list(params);
      companies.value = data.data?.companies?.items ?? [];
      meta.value = data.data?.companies?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load companies');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCompany(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.get(id);
      currentCompany.value = data.data?.company ?? null;
      return currentCompany.value;
    } catch (err) {
      state.applyError(err, 'Unable to load company');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createCompany(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.company;
    } catch (err) {
      state.applyError(err, 'Unable to create company');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateCompany(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.update(id, payload);
      currentCompany.value = data.data?.company ?? currentCompany.value;
      state.successMessage.value = data.message;
      return data.data?.company;
    } catch (err) {
      state.applyError(err, 'Unable to update company');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteCompany(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete company');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function restoreCompany(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.restore(id);
      currentCompany.value = data.data?.company ?? currentCompany.value;
      state.successMessage.value = data.message;
      return data.data?.company;
    } catch (err) {
      state.applyError(err, 'Unable to restore company');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function uploadLogo(id, file) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.uploadLogo(id, file);
      currentCompany.value = data.data?.company ?? currentCompany.value;
      state.successMessage.value = data.message;
      return data.data?.company;
    } catch (err) {
      state.applyError(err, 'Unable to upload logo');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateBranding(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.updateBranding(id, payload);
      currentCompany.value = data.data?.company ?? currentCompany.value;
      state.successMessage.value = data.message;
      return data.data?.company;
    } catch (err) {
      state.applyError(err, 'Unable to update branding');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  function resetFilters() {
    filters.value = {
      search: '',
      status: '',
      trashed: '',
      sort_by: 'created_at',
      sort_dir: 'desc',
      per_page: 10,
      page: 1,
    };
  }

  return {
    companies,
    meta,
    currentCompany,
    filters,
    ...state,
    fetchCompanies,
    fetchCompany,
    createCompany,
    updateCompany,
    deleteCompany,
    restoreCompany,
    uploadLogo,
    updateBranding,
    resetFilters,
  };
});

export const useDepartmentsStore = defineStore('departments', () => {
  const departments = ref([]);
  const meta = ref(null);
  const state = useAsyncState();

  async function fetchDepartments(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await companyService.listDepartments(params);
      departments.value = data.data?.departments?.items ?? [];
      meta.value = data.data?.departments?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load departments');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createDepartment(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.createDepartment(payload);
      state.successMessage.value = data.message;
      return data.data?.department;
    } catch (err) {
      state.applyError(err, 'Unable to create department');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateDepartment(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.updateDepartment(id, payload);
      state.successMessage.value = data.message;
      return data.data?.department;
    } catch (err) {
      state.applyError(err, 'Unable to update department');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteDepartment(id) {
    state.saving.value = true;
    try {
      await companyService.deleteDepartment(id);
    } catch (err) {
      state.applyError(err, 'Unable to delete department');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    departments,
    meta,
    ...state,
    fetchDepartments,
    createDepartment,
    updateDepartment,
    deleteDepartment,
  };
});

export const useTeamsStore = defineStore('teams', () => {
  const teams = ref([]);
  const meta = ref(null);
  const state = useAsyncState();

  async function fetchTeams(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await companyService.listTeams(params);
      teams.value = data.data?.teams?.items ?? [];
      meta.value = data.data?.teams?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load teams');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createTeam(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.createTeam(payload);
      state.successMessage.value = data.message;
      return data.data?.team;
    } catch (err) {
      state.applyError(err, 'Unable to create team');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateTeam(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.updateTeam(id, payload);
      state.successMessage.value = data.message;
      return data.data?.team;
    } catch (err) {
      state.applyError(err, 'Unable to update team');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteTeam(id) {
    state.saving.value = true;
    try {
      await companyService.deleteTeam(id);
    } catch (err) {
      state.applyError(err, 'Unable to delete team');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return { teams, meta, ...state, fetchTeams, createTeam, updateTeam, deleteTeam };
});

export const useLocationsStore = defineStore('locations', () => {
  const locations = ref([]);
  const meta = ref(null);
  const state = useAsyncState();

  async function fetchLocations(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await companyService.listLocations(params);
      locations.value = data.data?.locations?.items ?? [];
      meta.value = data.data?.locations?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load locations');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createLocation(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.createLocation(payload);
      state.successMessage.value = data.message;
      return data.data?.location;
    } catch (err) {
      state.applyError(err, 'Unable to create location');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateLocation(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await companyService.updateLocation(id, payload);
      state.successMessage.value = data.message;
      return data.data?.location;
    } catch (err) {
      state.applyError(err, 'Unable to update location');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteLocation(id) {
    state.saving.value = true;
    try {
      await companyService.deleteLocation(id);
    } catch (err) {
      state.applyError(err, 'Unable to delete location');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    locations,
    meta,
    ...state,
    fetchLocations,
    createLocation,
    updateLocation,
    deleteLocation,
  };
});
