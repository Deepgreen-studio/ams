import { defineStore } from 'pinia';
import { ref } from 'vue';
import { dpiaService } from '@/modules/compliance/services/dpiaService';

const defaultFilters = () => ({
  search: '',
  status: '',
  template_code: '',
  overall_risk_level: '',
  review_overdue: '',
  per_page: 10,
  page: 1,
});

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

export const useDpiaStore = defineStore('dpia', () => {
  const assessments = ref([]);
  const meta = ref(null);
  const current = ref(null);
  const risks = ref([]);
  const risksMeta = ref(null);
  const currentRisk = ref(null);
  const templates = ref([]);
  const riskMatrix = ref(null);
  const dpiaStatistics = ref(null);
  const riskStatistics = ref(null);
  const recentAssessments = ref([]);
  const pendingApproval = ref([]);
  const mitigationQueue = ref([]);
  const filters = ref(defaultFilters());
  const state = useAsyncState();

  function resetFilters() {
    filters.value = defaultFilters();
  }

  async function fetchDashboard(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await dpiaService.dashboard(company ? { company } : {});
      dpiaStatistics.value = data.data?.dpia_statistics ?? null;
      riskStatistics.value = data.data?.risk_statistics ?? null;
      recentAssessments.value = data.data?.recent_assessments ?? [];
      pendingApproval.value = data.data?.pending_approval ?? [];
      mitigationQueue.value = data.data?.mitigation_queue ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load DPIA dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTemplates() {
    const { data } = await dpiaService.templates();
    templates.value = data.data?.templates ?? [];
    return templates.value;
  }

  async function fetchRiskMatrix(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await dpiaService.riskMatrix(company ? { company } : {});
      riskMatrix.value = data.data ?? null;
      return riskMatrix.value;
    } catch (err) {
      state.applyError(err, 'Unable to load risk matrix');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchAssessments(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await dpiaService.list(params);
      assessments.value = data.data?.assessments?.items ?? [];
      meta.value = data.data?.assessments?.meta ?? null;
      dpiaStatistics.value = data.data?.dpia_statistics ?? dpiaStatistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load DPIA assessments');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchAssessment(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await dpiaService.get(id);
      current.value = data.data?.assessment ?? null;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to load DPIA assessment');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createAssessment(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await dpiaService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.assessment;
    } catch (err) {
      state.applyError(err, 'Unable to create DPIA');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function runAssessmentAction(method, id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await dpiaService[method](id, payload);
      current.value = data.data?.assessment ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.assessment;
    } catch (err) {
      state.applyError(err, 'Unable to update DPIA');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchRisks(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = Object.fromEntries(
        Object.entries(overrides).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await dpiaService.risks(params);
      risks.value = data.data?.risks?.items ?? [];
      risksMeta.value = data.data?.risks?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load risk register');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchMitigation(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = Object.fromEntries(
        Object.entries(overrides).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await dpiaService.mitigation(params);
      risks.value = data.data?.risks?.items ?? [];
      risksMeta.value = data.data?.risks?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load mitigation tracker');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRisk(id) {
    state.loading.value = true;
    try {
      const { data } = await dpiaService.getRisk(id);
      currentRisk.value = data.data?.risk ?? null;
      return currentRisk.value;
    } catch (err) {
      state.applyError(err, 'Unable to load risk');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createRisk(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await dpiaService.createRisk(payload);
      state.successMessage.value = data.message;
      return data.data?.risk;
    } catch (err) {
      state.applyError(err, 'Unable to register risk');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    assessments,
    meta,
    current,
    risks,
    risksMeta,
    currentRisk,
    templates,
    riskMatrix,
    dpiaStatistics,
    riskStatistics,
    recentAssessments,
    pendingApproval,
    mitigationQueue,
    filters,
    ...state,
    resetFilters,
    fetchDashboard,
    fetchTemplates,
    fetchRiskMatrix,
    fetchAssessments,
    fetchAssessment,
    createAssessment,
    saveWizard: (id, payload) => runAssessmentAction('saveWizard', id, payload),
    submit: (id, payload) => runAssessmentAction('submit', id, payload),
    approve: (id, payload) => runAssessmentAction('approve', id, payload),
    reject: (id, payload) => runAssessmentAction('reject', id, payload),
    fetchRisks,
    fetchMitigation,
    fetchRisk,
    createRisk,
    assessRisk: async (id, payload) => {
      state.saving.value = true;
      state.clearMessages();
      try {
        const { data } = await dpiaService.assessRisk(id, payload);
        currentRisk.value = data.data?.risk ?? currentRisk.value;
        state.successMessage.value = data.message;
        return data.data?.risk;
      } catch (err) {
        state.applyError(err, 'Unable to score risk');
        throw err;
      } finally {
        state.saving.value = false;
      }
    },
    addRiskAction: async (id, payload) => {
      state.saving.value = true;
      state.clearMessages();
      try {
        const { data } = await dpiaService.addRiskAction(id, payload);
        state.successMessage.value = data.message;
        await fetchRisk(id);
        return data.data?.action;
      } catch (err) {
        state.applyError(err, 'Unable to add mitigation action');
        throw err;
      } finally {
        state.saving.value = false;
      }
    },
    completeRiskAction: async (id, actionId) => {
      state.saving.value = true;
      try {
        const { data } = await dpiaService.completeRiskAction(id, actionId);
        state.successMessage.value = data.message;
        await fetchRisk(id);
        return data.data?.action;
      } catch (err) {
        state.applyError(err, 'Unable to complete action');
        throw err;
      } finally {
        state.saving.value = false;
      }
    },
  };
});
