import { defineStore } from 'pinia';
import { ref } from 'vue';
import { contentService } from '@/modules/content/services/contentService';

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

const defaultFilters = () => ({
  search: '',
  type: '',
  status: '',
  category: '',
  tag: '',
  is_featured: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useContentStore = defineStore('content', () => {
  const contents = ref([]);
  const meta = ref(null);
  const statistics = ref(null);
  const currentContent = ref(null);
  const versions = ref([]);
  const versionMeta = ref(null);
  const comparison = ref(null);
  const workflowHistory = ref([]);
  const queue = ref([]);
  const queueMeta = ref(null);
  const types = ref([]);
  const statuses = ref([]);
  const categories = ref([]);
  const tags = ref([]);
  const filters = ref(defaultFilters());
  const state = useAsyncState();

  async function fetchDashboard() {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.dashboard();
      statistics.value = data.data?.statistics ?? null;
      return statistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load content dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchContents(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await contentService.list(params);
      contents.value = data.data?.contents?.items ?? [];
      meta.value = data.data?.contents?.meta ?? null;
      statistics.value = data.data?.statistics ?? statistics.value;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to load content');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchContent(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.get(id);
      currentContent.value = data.data?.content ?? null;
      return currentContent.value;
    } catch (err) {
      state.applyError(err, 'Unable to load content item');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCatalog() {
    const [typesRes, statusesRes, categoriesRes, tagsRes] = await Promise.all([
      contentService.types(),
      contentService.statuses(),
      contentService.categories({ per_page: 100, status: 'active', sort_by: 'sort_order', sort_dir: 'asc' }),
      contentService.tags({ per_page: 100, status: 'active', sort_by: 'sort_order', sort_dir: 'asc' }),
    ]);

    types.value = typesRes.data.data?.types ?? [];
    statuses.value = statusesRes.data.data?.statuses ?? [];
    categories.value = categoriesRes.data.data?.categories?.items ?? [];
    tags.value = tagsRes.data.data?.tags?.items ?? [];
  }

  async function createContent(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.create(payload);
      state.successMessage.value = data.message || 'Content created successfully.';
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to create content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateContent(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.update(id, payload);
      currentContent.value = data.data?.content ?? currentContent.value;
      state.successMessage.value = data.message || 'Content updated successfully.';
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to update content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteContent(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.remove(id);
      state.successMessage.value = data.message || 'Content deleted successfully.';
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function restoreContent(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.restore(id);
      currentContent.value = data.data?.content ?? currentContent.value;
      state.successMessage.value = data.message || 'Content restored successfully.';
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to restore content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function publishContent(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.publish(id, payload);
      currentContent.value = data.data?.content ?? currentContent.value;
      state.successMessage.value = data.message || 'Content published successfully.';
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to publish content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function unpublishContent(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.unpublish(id);
      currentContent.value = data.data?.content ?? currentContent.value;
      state.successMessage.value = data.message || 'Content unpublished successfully.';
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to unpublish content');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function autosaveContent(id, payload) {
    try {
      const { data } = await contentService.autosave(id, payload);
      currentContent.value = data.data?.content ?? currentContent.value;
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to autosave draft');
      throw err;
    }
  }

  async function fetchVersions(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.versions(id);
      versions.value = data.data?.versions ?? [];
      versionMeta.value = data.data?.content ?? null;
      return versions.value;
    } catch (err) {
      state.applyError(err, 'Unable to load version history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function compareVersions(id, from, to) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.compareVersions(id, from, to);
      comparison.value = data.data ?? null;
      return comparison.value;
    } catch (err) {
      state.applyError(err, 'Unable to compare versions');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function restoreVersion(id, versionId, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.restoreVersion(id, versionId, payload);
      currentContent.value = data.data?.content ?? currentContent.value;
      state.successMessage.value = data.message || 'Version restored successfully.';
      await fetchVersions(id);
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to restore version');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchWorkflowQueue(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.workflowQueue(overrides);
      queue.value = data.data?.contents?.items ?? [];
      queueMeta.value = data.data?.contents?.meta ?? null;
      statistics.value = data.data?.statistics ?? statistics.value;
      return queue.value;
    } catch (err) {
      state.applyError(err, 'Unable to load approval queue');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchWorkflowHistory(id) {
    const { data } = await contentService.workflowHistory(id);
    workflowHistory.value = data.data?.history ?? [];
    return workflowHistory.value;
  }

  async function runWorkflow(action, id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const map = {
        submit: contentService.submitForReview,
        review: contentService.markReviewed,
        approve: contentService.approveContent,
        reject: contentService.rejectContent,
        publish: contentService.publishWorkflow,
        archive: contentService.archiveContent,
        returnToDraft: contentService.returnToDraft,
      };
      const fn = map[action];
      if (!fn) throw new Error('Unknown workflow action');
      const { data } = await fn(id, payload);
      currentContent.value = data.data?.content ?? currentContent.value;
      state.successMessage.value = data.message || 'Workflow updated.';
      await fetchWorkflowHistory(id).catch(() => {});
      return data.data?.content;
    } catch (err) {
      state.applyError(err, 'Unable to update workflow');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  function resetFilters() {
    filters.value = defaultFilters();
  }

  return {
    contents,
    meta,
    statistics,
    currentContent,
    versions,
    versionMeta,
    comparison,
    workflowHistory,
    queue,
    queueMeta,
    types,
    statuses,
    categories,
    tags,
    filters,
    ...state,
    fetchDashboard,
    fetchContents,
    fetchContent,
    fetchCatalog,
    createContent,
    updateContent,
    deleteContent,
    restoreContent,
    publishContent,
    unpublishContent,
    autosaveContent,
    fetchVersions,
    compareVersions,
    restoreVersion,
    fetchWorkflowQueue,
    fetchWorkflowHistory,
    runWorkflow,
    resetFilters,
  };
});
