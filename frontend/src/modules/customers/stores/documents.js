import { defineStore } from 'pinia';
import { ref } from 'vue';
import { customerDocumentService } from '@/modules/customers/services/customerDocumentService';

const defaultFilters = () => ({
  search: '',
  category: '',
  status: '',
  customer: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 12,
  page: 1,
});

export const useCustomerDocumentsStore = defineStore('customerDocuments', () => {
  const documents = ref([]);
  const folders = ref([]);
  const statistics = ref(null);
  const versions = ref([]);
  const meta = ref(null);
  const currentDocument = ref(null);
  const timeline = ref([]);
  const previewUrl = ref(null);
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

  function revokePreview() {
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value);
      previewUrl.value = null;
    }
  }

  async function fetchLibrary(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await customerDocumentService.list(params);
      documents.value = data.data?.documents?.items ?? [];
      meta.value = data.data?.documents?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      folders.value = data.data?.folders ?? [];
      return data;
    } catch (err) {
      applyError(err, 'Unable to load document library');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchDocument(id) {
    loading.value = true;
    clearMessages();
    revokePreview();

    try {
      const { data } = await customerDocumentService.get(id);
      currentDocument.value = data.data?.document ?? null;
      return currentDocument.value;
    } catch (err) {
      applyError(err, 'Unable to load document');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchVersions(id) {
    try {
      const { data } = await customerDocumentService.versions(id);
      versions.value = data.data?.versions ?? [];
      return versions.value;
    } catch (err) {
      applyError(err, 'Unable to load version history');
      throw err;
    }
  }

  async function fetchTimeline(id, limit = 50) {
    try {
      const { data } = await customerDocumentService.timeline(id, { limit });
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load document timeline');
      throw err;
    }
  }

  async function loadPreview(id) {
    revokePreview();
    try {
      const { data } = await customerDocumentService.preview(id);
      previewUrl.value = URL.createObjectURL(data);
      return previewUrl.value;
    } catch (err) {
      applyError(err, 'Unable to preview document');
      throw err;
    }
  }

  async function downloadDocument(id, filename = 'document') {
    try {
      const { data } = await customerDocumentService.download(id);
      const url = URL.createObjectURL(data);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      link.click();
      URL.revokeObjectURL(url);
    } catch (err) {
      applyError(err, 'Unable to download document');
      throw err;
    }
  }

  async function uploadDocument(formData) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerDocumentService.upload(formData);
      successMessage.value = data.message || 'Document uploaded successfully.';
      return data.data?.document;
    } catch (err) {
      applyError(err, 'Unable to upload document');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function uploadVersion(id, formData) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerDocumentService.uploadVersion(id, formData);
      currentDocument.value = data.data?.document ?? currentDocument.value;
      successMessage.value = data.message || 'Document version uploaded successfully.';
      return data.data?.document;
    } catch (err) {
      applyError(err, 'Unable to upload document version');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateDocument(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerDocumentService.update(id, payload);
      currentDocument.value = data.data?.document ?? currentDocument.value;
      successMessage.value = data.message || 'Document updated successfully.';
      return data.data?.document;
    } catch (err) {
      applyError(err, 'Unable to update document');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveDocument(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerDocumentService.remove(id);
      successMessage.value = data.message || 'Document archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive document');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreDocument(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerDocumentService.restore(id);
      currentDocument.value = data.data?.document ?? currentDocument.value;
      successMessage.value = data.message || 'Document restored successfully.';
      return data.data?.document;
    } catch (err) {
      applyError(err, 'Unable to restore document');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters(customer = '') {
    filters.value = { ...defaultFilters(), customer };
  }

  return {
    documents,
    folders,
    statistics,
    versions,
    meta,
    currentDocument,
    timeline,
    previewUrl,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    fetchLibrary,
    fetchDocument,
    fetchVersions,
    fetchTimeline,
    loadPreview,
    downloadDocument,
    uploadDocument,
    uploadVersion,
    updateDocument,
    archiveDocument,
    restoreDocument,
    resetFilters,
    clearMessages,
    revokePreview,
  };
});
