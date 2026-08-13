import { defineStore } from 'pinia';
import { ref } from 'vue';
import { knowledgeBaseService } from '@/modules/support/services/knowledgeBaseService';

export const useKnowledgeBaseStore = defineStore('knowledgeBase', () => {
  const dashboard = ref(null);
  const articles = ref([]);
  const meta = ref(null);
  const currentArticle = ref(null);
  const related = ref([]);
  const versions = ref([]);
  const categories = ref([]);
  const tags = ref([]);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);
  const filters = ref({
    search: '',
    type: '',
    status: '',
    category: '',
    tag: '',
    per_page: 10,
    page: 1,
  });

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    const payload = err?.response?.data ?? err;
    const firstFieldError = payload?.errors
      ? Object.values(payload.errors).flat().find(Boolean)
      : null;
    error.value = firstFieldError || payload?.message || err?.message || fallback;
  }

  function cleanParams(source) {
    return Object.fromEntries(
      Object.entries(source).filter(([, value]) => value !== '' && value !== null && value !== undefined)
    );
  }

  async function fetchDashboard() {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.dashboard();
      dashboard.value = data.data ?? null;
      categories.value = data.data?.categories ?? [];
      return dashboard.value;
    } catch (err) {
      applyError(err, 'Unable to load knowledge center');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchArticles(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const { data } = await knowledgeBaseService.articles(cleanParams(filters.value));
      articles.value = data.data?.articles?.items ?? [];
      meta.value = data.data?.articles?.meta ?? null;
      return articles.value;
    } catch (err) {
      applyError(err, 'Unable to load articles');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchArticle(id) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.getArticle(id);
      currentArticle.value = data.data?.article ?? null;
      related.value = data.data?.related ?? [];
      return currentArticle.value;
    } catch (err) {
      applyError(err, 'Unable to load article');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createArticle(payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.createArticle(payload);
      successMessage.value = data.message || 'Article created.';
      currentArticle.value = data.data?.article?.uuid
        ? data.data.article
        : data.data?.article?.data ?? data.data?.article ?? null;
      return currentArticle.value;
    } catch (err) {
      applyError(err, 'Unable to create article');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateArticle(id, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.updateArticle(id, payload);
      successMessage.value = data.message || 'Article updated.';
      currentArticle.value = data.data?.article ?? null;
      return currentArticle.value;
    } catch (err) {
      applyError(err, 'Unable to update article');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function publishArticle(id) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.publishArticle(id);
      successMessage.value = data.message || 'Article published.';
      currentArticle.value = data.data?.article ?? null;
      return currentArticle.value;
    } catch (err) {
      applyError(err, 'Unable to publish article');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function submitFeedback(id, isHelpful, comment = null) {
    try {
      const { data } = await knowledgeBaseService.feedback(id, {
        is_helpful: isHelpful,
        comment,
      });
      if (currentArticle.value) {
        currentArticle.value = {
          ...currentArticle.value,
          helpful_count: data.data?.article?.helpful_count ?? currentArticle.value.helpful_count,
          not_helpful_count: data.data?.article?.not_helpful_count ?? currentArticle.value.not_helpful_count,
          user_feedback: isHelpful,
        };
      }
      successMessage.value = data.message || 'Thanks for your feedback.';
    } catch (err) {
      applyError(err, 'Unable to submit feedback');
      throw err;
    }
  }

  async function fetchVersions(id) {
    try {
      const { data } = await knowledgeBaseService.versions(id);
      versions.value = data.data?.versions ?? [];
      return versions.value;
    } catch (err) {
      applyError(err, 'Unable to load versions');
      throw err;
    }
  }

  async function restoreVersion(id, versionId) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.restoreVersion(id, versionId);
      successMessage.value = data.message || 'Version restored.';
      currentArticle.value = data.data?.article ?? null;
      await fetchVersions(id);
      return currentArticle.value;
    } catch (err) {
      applyError(err, 'Unable to restore version');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function linkCms(id, contentId, sync = true) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await knowledgeBaseService.linkCms(id, { content_id: contentId, sync });
      successMessage.value = data.message || 'Linked to CMS.';
      currentArticle.value = data.data?.article ?? null;
      return currentArticle.value;
    } catch (err) {
      applyError(err, 'Unable to link CMS content');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchCategories() {
    const { data } = await knowledgeBaseService.categories({ tree: true });
    categories.value = data.data?.categories ?? [];
    return categories.value;
  }

  async function fetchTags() {
    const { data } = await knowledgeBaseService.tags();
    tags.value = data.data?.tags ?? [];
    return tags.value;
  }

  return {
    dashboard,
    articles,
    meta,
    currentArticle,
    related,
    versions,
    categories,
    tags,
    loading,
    saving,
    error,
    successMessage,
    filters,
    fetchDashboard,
    fetchArticles,
    fetchArticle,
    createArticle,
    updateArticle,
    publishArticle,
    submitFeedback,
    fetchVersions,
    restoreVersion,
    linkCms,
    fetchCategories,
    fetchTags,
    clearMessages,
  };
});
