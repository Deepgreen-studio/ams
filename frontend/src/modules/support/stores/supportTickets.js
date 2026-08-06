import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { supportTicketService } from '@/modules/support/services/supportTicketService';

const defaultFilters = () => ({
  search: '',
  status: '',
  priority: '',
  category: '',
  source: '',
  company: '',
  customer: '',
  application: '',
  unassigned: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useSupportTicketsStore = defineStore('supportTickets', () => {
  const tickets = ref([]);
  const meta = ref(null);
  const statistics = ref(null);
  const dashboard = ref(null);
  const boardColumns = ref([]);
  const timeline = ref([]);
  const messages = ref([]);
  const unreadCount = ref(0);
  const agents = ref([]);
  const currentTicket = ref(null);
  const filters = ref(defaultFilters());
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  const totalTickets = computed(() => meta.value?.total ?? 0);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  function cleanParams(source) {
    return Object.fromEntries(
      Object.entries(source).filter(([, value]) => value !== '' && value !== null && value !== undefined)
    );
  }

  async function fetchDashboard(params = {}) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.dashboard(cleanParams(params));
      dashboard.value = data.data ?? null;
      statistics.value = data.data?.statistics ?? null;
      return dashboard.value;
    } catch (err) {
      applyError(err, 'Unable to load support dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTickets(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const { data } = await supportTicketService.list(cleanParams(filters.value));
      tickets.value = data.data?.tickets?.items ?? [];
      meta.value = data.data?.tickets?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load support tickets');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchBoard(params = {}) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.board(cleanParams(params));
      boardColumns.value = data.data?.columns ?? [];
      statistics.value = data.data?.statistics ?? null;
      return boardColumns.value;
    } catch (err) {
      applyError(err, 'Unable to load kanban board');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchQueue(params = {}) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.queue(cleanParams(params));
      tickets.value = data.data?.tickets?.items ?? [];
      meta.value = data.data?.tickets?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load ticket queue');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAgents() {
    try {
      const { data } = await supportTicketService.agents();
      agents.value = data.data?.agents ?? [];
      return agents.value;
    } catch (err) {
      applyError(err, 'Unable to load agents');
      throw err;
    }
  }

  async function fetchTicket(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.get(id);
      currentTicket.value = data.data?.ticket ?? null;
      return currentTicket.value;
    } catch (err) {
      applyError(err, 'Unable to load support ticket');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(id) {
    try {
      const { data } = await supportTicketService.timeline(id);
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load status timeline');
      throw err;
    }
  }

  async function fetchMessages(id) {
    loading.value = true;

    try {
      const { data } = await supportTicketService.messages(id);
      messages.value = data.data?.messages ?? [];
      unreadCount.value = data.data?.unread_count ?? 0;
      return messages.value;
    } catch (err) {
      applyError(err, 'Unable to load conversation');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function postMessage(id, formData) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.postMessage(id, formData);
      await fetchMessages(id);
      successMessage.value = data.message || 'Message posted successfully.';
      return data.data?.message;
    } catch (err) {
      applyError(err, 'Unable to post message');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function markMessagesRead(id, messageIds = null) {
    try {
      const payload = messageIds ? { message_ids: messageIds } : {};
      const { data } = await supportTicketService.markMessagesRead(id, payload);
      await fetchMessages(id);
      return data.data?.marked ?? 0;
    } catch (err) {
      applyError(err, 'Unable to mark messages as read');
      throw err;
    }
  }

  async function deleteMessage(ticketId, messageId) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.deleteMessage(ticketId, messageId);
      await fetchMessages(ticketId);
      successMessage.value = data.message || 'Message deleted successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to delete message');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function createTicket(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.create(payload);
      successMessage.value = data.message || 'Support ticket created successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to create support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateTicket(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.update(id, payload);
      currentTicket.value = data.data?.ticket ?? currentTicket.value;
      successMessage.value = data.message || 'Support ticket updated successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to update support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function transitionTicket(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.transition(id, payload);
      currentTicket.value = data.data?.ticket ?? currentTicket.value;
      successMessage.value = data.message || 'Ticket status updated successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to change ticket status');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function assignTicket(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.assign(id, payload);
      currentTicket.value = data.data?.ticket ?? currentTicket.value;
      successMessage.value = data.message || 'Support ticket assigned successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to assign support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function closeTicket(id, payload = {}) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.close(id, payload);
      currentTicket.value = data.data?.ticket ?? currentTicket.value;
      successMessage.value = data.message || 'Support ticket closed successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to close support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function reopenTicket(id, payload = {}) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.reopen(id, payload);
      currentTicket.value = data.data?.ticket ?? currentTicket.value;
      successMessage.value = data.message || 'Support ticket reopened successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to reopen support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveTicket(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.remove(id);
      successMessage.value = data.message || 'Support ticket archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreTicket(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await supportTicketService.restore(id);
      currentTicket.value = data.data?.ticket ?? currentTicket.value;
      successMessage.value = data.message || 'Support ticket restored successfully.';
      return data.data?.ticket;
    } catch (err) {
      applyError(err, 'Unable to restore support ticket');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters() {
    filters.value = defaultFilters();
  }

  return {
    tickets,
    meta,
    statistics,
    dashboard,
    boardColumns,
    timeline,
    messages,
    unreadCount,
    agents,
    currentTicket,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    totalTickets,
    fetchDashboard,
    fetchTickets,
    fetchBoard,
    fetchQueue,
    fetchAgents,
    fetchTicket,
    fetchTimeline,
    fetchMessages,
    postMessage,
    markMessagesRead,
    deleteMessage,
    createTicket,
    updateTicket,
    transitionTicket,
    assignTicket,
    closeTicket,
    reopenTicket,
    archiveTicket,
    restoreTicket,
    resetFilters,
    clearMessages,
  };
});
