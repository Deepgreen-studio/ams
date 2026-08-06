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

export const useTaxonomyStore = defineStore('content-taxonomy', () => {
  const categories = ref([]);
  const categoryMeta = ref(null);
  const categoryTree = ref([]);
  const tags = ref([]);
  const tagMeta = ref(null);
  const selectedCategoryIds = ref([]);
  const selectedTagIds = ref([]);
  const categoryFilters = ref({
    search: '',
    status: '',
    parent_id: '',
    trashed: '',
    sort_by: 'sort_order',
    sort_dir: 'asc',
    per_page: 15,
    page: 1,
  });
  const tagFilters = ref({
    search: '',
    status: '',
    trashed: '',
    sort_by: 'sort_order',
    sort_dir: 'asc',
    per_page: 15,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchCategories(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    categoryFilters.value = { ...categoryFilters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(categoryFilters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await contentService.categories(params);
      categories.value = data.data?.categories?.items ?? [];
      categoryMeta.value = data.data?.categories?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load categories');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCategoryTree(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.categoryTree(overrides);
      categoryTree.value = data.data?.tree ?? [];
      return categoryTree.value;
    } catch (err) {
      state.applyError(err, 'Unable to load category tree');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createCategory(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.createCategory(payload);
      state.successMessage.value = data.message;
      return data.data?.category;
    } catch (err) {
      state.applyError(err, 'Unable to create category');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateCategory(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.updateCategory(id, payload);
      state.successMessage.value = data.message;
      return data.data?.category;
    } catch (err) {
      state.applyError(err, 'Unable to update category');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteCategory(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.removeCategory(id);
      state.successMessage.value = data.message;
      selectedCategoryIds.value = selectedCategoryIds.value.filter((value) => value !== id);
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete category');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function bulkCategories(action, ids = selectedCategoryIds.value) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.bulkCategories({ action, ids });
      state.successMessage.value = data.message;
      selectedCategoryIds.value = [];
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to run category bulk action');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchTags(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    tagFilters.value = { ...tagFilters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(tagFilters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await contentService.tags(params);
      tags.value = data.data?.tags?.items ?? [];
      tagMeta.value = data.data?.tags?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load tags');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createTag(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.createTag(payload);
      state.successMessage.value = data.message;
      return data.data?.tag;
    } catch (err) {
      state.applyError(err, 'Unable to create tag');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateTag(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.updateTag(id, payload);
      state.successMessage.value = data.message;
      return data.data?.tag;
    } catch (err) {
      state.applyError(err, 'Unable to update tag');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteTag(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.removeTag(id);
      state.successMessage.value = data.message;
      selectedTagIds.value = selectedTagIds.value.filter((value) => value !== id);
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete tag');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function bulkTags(action, ids = selectedTagIds.value) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await contentService.bulkTags({ action, ids });
      state.successMessage.value = data.message;
      selectedTagIds.value = [];
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to run tag bulk action');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  function toggleCategorySelection(id) {
    selectedCategoryIds.value = selectedCategoryIds.value.includes(id)
      ? selectedCategoryIds.value.filter((value) => value !== id)
      : [...selectedCategoryIds.value, id];
  }

  function toggleTagSelection(id) {
    selectedTagIds.value = selectedTagIds.value.includes(id)
      ? selectedTagIds.value.filter((value) => value !== id)
      : [...selectedTagIds.value, id];
  }

  function toggleSelectAllCategories(ids = []) {
    if (ids.length && ids.every((id) => selectedCategoryIds.value.includes(id))) {
      selectedCategoryIds.value = selectedCategoryIds.value.filter((id) => !ids.includes(id));
      return;
    }
    selectedCategoryIds.value = [...new Set([...selectedCategoryIds.value, ...ids])];
  }

  function toggleSelectAllTags(ids = []) {
    if (ids.length && ids.every((id) => selectedTagIds.value.includes(id))) {
      selectedTagIds.value = selectedTagIds.value.filter((id) => !ids.includes(id));
      return;
    }
    selectedTagIds.value = [...new Set([...selectedTagIds.value, ...ids])];
  }

  return {
    categories,
    categoryMeta,
    categoryTree,
    tags,
    tagMeta,
    selectedCategoryIds,
    selectedTagIds,
    categoryFilters,
    tagFilters,
    ...state,
    fetchCategories,
    fetchCategoryTree,
    createCategory,
    updateCategory,
    deleteCategory,
    bulkCategories,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
    bulkTags,
    toggleCategorySelection,
    toggleTagSelection,
    toggleSelectAllCategories,
    toggleSelectAllTags,
  };
});
