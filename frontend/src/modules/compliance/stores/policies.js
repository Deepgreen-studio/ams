import { defineStore } from 'pinia';
import { ref } from 'vue';
import { policyDocumentService } from '@/modules/compliance/services/policyDocumentService';

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

export const usePolicyStore = defineStore('policies', () => {
  const policies = ref([]);
  const meta = ref(null);
  const current = ref(null);
  const versions = ref([]);
  const versionMeta = ref(null);
  const comparison = ref(null);
  const approvals = ref([]);
  const approvalsMeta = ref(null);
  const statistics = ref(null);
  const recent = ref([]);
  const approvalQueuePreview = ref([]);
  const cmsLink = ref({ linked: false, content: null, versions: [] });
  const filters = ref({
    search: '',
    status: '',
    policy_type: '',
    company: '',
    sort_by: 'updated_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchDashboard(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.dashboard(company ? { company } : {});
      statistics.value = data.data?.statistics ?? null;
      recent.value = data.data?.recent ?? [];
      approvalQueuePreview.value = data.data?.approval_queue ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load policy dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchPolicies(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await policyDocumentService.list(params);
      policies.value = data.data?.policies?.items ?? [];
      meta.value = data.data?.policies?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load policies');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchPolicy(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.get(id);
      current.value = data.data?.policy ?? null;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to load policy');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createPolicy(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to create policy');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updatePolicy(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.update(id, payload);
      current.value = data.data?.policy ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to update policy');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchVersions(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.versions(id);
      versions.value = data.data?.versions ?? [];
      versionMeta.value = data.data?.meta ?? null;
      return versions.value;
    } catch (err) {
      state.applyError(err, 'Unable to load policy versions');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function compareVersions(id, from, to) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.compare(id, from, to);
      comparison.value = data.data ?? null;
      return comparison.value;
    } catch (err) {
      state.applyError(err, 'Unable to compare versions');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function restoreVersion(id, version, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.restoreVersion(id, version, payload);
      current.value = data.data?.policy ?? current.value;
      state.successMessage.value = data.message;
      await fetchVersions(id);
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to restore version');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function submitPolicy(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.submit(id, payload);
      current.value = data.data?.policy ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to submit policy');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function publishPolicy(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.publish(id, payload);
      current.value = data.data?.policy ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to publish policy');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchApprovals(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = Object.fromEntries(
        Object.entries(overrides).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await policyDocumentService.approvalQueue(params);
      approvals.value = data.data?.approvals?.items ?? [];
      approvalsMeta.value = data.data?.approvals?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load approval queue');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function decideApproval(method, approvalId, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService[method](approvalId, payload);
      state.successMessage.value = data.message;
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to decide approval');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchCmsVersions(id) {
    try {
      const { data } = await policyDocumentService.cmsVersions(id);
      cmsLink.value = {
        linked: Boolean(data.data?.linked),
        content: data.data?.content ?? null,
        versions: data.data?.versions ?? [],
      };
      return cmsLink.value;
    } catch (err) {
      state.applyError(err, 'Unable to load CMS version history');
      throw err;
    }
  }

  async function linkCms(id, contentId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await policyDocumentService.linkCms(id, { content_id: contentId });
      current.value = data.data?.policy ?? current.value;
      state.successMessage.value = data.message;
      await fetchCmsVersions(id);
      return data.data?.policy;
    } catch (err) {
      state.applyError(err, 'Unable to link CMS content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    policies,
    meta,
    current,
    versions,
    versionMeta,
    comparison,
    approvals,
    approvalsMeta,
    statistics,
    recent,
    approvalQueuePreview,
    cmsLink,
    filters,
    ...state,
    fetchDashboard,
    fetchPolicies,
    fetchPolicy,
    createPolicy,
    updatePolicy,
    fetchVersions,
    compareVersions,
    restoreVersion,
    submitPolicy,
    publishPolicy,
    fetchApprovals,
    approve: (approvalId, payload) => decideApproval('approve', approvalId, payload),
    reject: (approvalId, payload) => decideApproval('reject', approvalId, payload),
    fetchCmsVersions,
    linkCms,
  };
});
