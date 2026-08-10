import { defineStore } from 'pinia';
import { ref } from 'vue';
import {
  communicationCenterService,
  customerCommunicationService,
  customerNoteService,
  customerTaskService,
} from '@/modules/customers/services/communicationService';

export const useCommunicationStore = defineStore('customerCommunication', () => {
  const overview = ref(null);
  const timeline = ref([]);
  const activity = ref([]);
  const reminders = ref([]);
  const notes = ref([]);
  const tasks = ref([]);
  const communications = ref([]);
  const noteStats = ref(null);
  const taskStats = ref(null);
  const communicationStats = ref(null);
  const notesMeta = ref(null);
  const tasksMeta = ref(null);
  const communicationsMeta = ref(null);
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

  async function fetchOverview(customer) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await communicationCenterService.overview({ customer });
      overview.value = data.data ?? null;
      timeline.value = data.data?.timeline ?? [];
      activity.value = data.data?.activity ?? [];
      reminders.value = data.data?.reminders ?? [];
      return data;
    } catch (err) {
      applyError(err, 'Unable to load communication center');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchNotes(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await customerNoteService.list(params);
      notes.value = data.data?.notes?.items ?? [];
      notesMeta.value = data.data?.notes?.meta ?? null;
      noteStats.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load notes');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTasks(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await customerTaskService.list(params);
      tasks.value = data.data?.tasks?.items ?? [];
      tasksMeta.value = data.data?.tasks?.meta ?? null;
      taskStats.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load tasks');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCommunications(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await customerCommunicationService.list(params);
      communications.value = data.data?.communications?.items ?? [];
      communicationsMeta.value = data.data?.communications?.meta ?? null;
      communicationStats.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load email history');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCalendar(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await communicationCenterService.calendar(params);
      reminders.value = data.data?.reminders ?? [];
      return data;
    } catch (err) {
      applyError(err, 'Unable to load reminder calendar');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(params = {}) {
    try {
      const { data } = await communicationCenterService.timeline(params);
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load communication timeline');
      throw err;
    }
  }

  async function fetchActivity(params = {}) {
    try {
      const { data } = await communicationCenterService.activity(params);
      activity.value = data.data?.activity ?? [];
      return activity.value;
    } catch (err) {
      applyError(err, 'Unable to load activity timeline');
      throw err;
    }
  }

  async function createNote(payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerNoteService.create(payload);
      successMessage.value = data.message || 'Note created successfully.';
      return data.data?.note;
    } catch (err) {
      applyError(err, 'Unable to create note');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function createTask(payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerTaskService.create(payload);
      successMessage.value = data.message || 'Task created successfully.';
      return data.data?.task;
    } catch (err) {
      applyError(err, 'Unable to create task');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function completeTask(id) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerTaskService.complete(id);
      successMessage.value = data.message || 'Task completed successfully.';
      return data.data?.task;
    } catch (err) {
      applyError(err, 'Unable to complete task');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function createCommunication(payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerCommunicationService.create(payload);
      successMessage.value = data.message || 'Communication logged successfully.';
      return data.data?.communication;
    } catch (err) {
      applyError(err, 'Unable to log communication');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveNote(id) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerNoteService.remove(id);
      successMessage.value = data.message || 'Note deleted successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to delete note');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveTask(id) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerTaskService.remove(id);
      successMessage.value = data.message || 'Task deleted successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to delete task');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveCommunication(id) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await customerCommunicationService.remove(id);
      successMessage.value = data.message || 'Communication deleted successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to delete communication');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  return {
    overview,
    timeline,
    activity,
    reminders,
    notes,
    tasks,
    communications,
    noteStats,
    taskStats,
    communicationStats,
    notesMeta,
    tasksMeta,
    communicationsMeta,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    fetchOverview,
    fetchNotes,
    fetchTasks,
    fetchCommunications,
    fetchCalendar,
    fetchTimeline,
    fetchActivity,
    createNote,
    createTask,
    completeTask,
    createCommunication,
    archiveNote,
    archiveTask,
    archiveCommunication,
    clearMessages,
  };
});
