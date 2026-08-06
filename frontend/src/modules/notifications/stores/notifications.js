import { defineStore } from 'pinia';
import { notificationService } from '@/modules/notifications/services/notificationService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

const defaultMeta = { current_page: 1, last_page: 1, per_page: 20, total: 0 };

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [],
    recent: [],
    meta: { ...defaultMeta },
    unreadCount: 0,
    preferences: [],
    channels: {},
    channelCatalog: [],
    events: [],
    statuses: [],
    priorities: [],
    templates: [],
    templateEvents: [],
    templateChannels: [],
    templateLocales: [],
    templateWorkflowStatuses: [],
    templateMeta: { ...defaultMeta },
    currentTemplate: null,
    templateVersions: [],
    templateVersionMeta: null,
    templateApprovals: [],
    templateApprovalMeta: { ...defaultMeta },
    templatePreview: null,
    logs: [],
    logStats: null,
    logMeta: { ...defaultMeta },
    dashboardStats: null,
    deliveryStats: null,
    loading: false,
    saving: false,
    error: null,
    successMessage: null,
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await notificationService.dashboard();
        this.dashboardStats = data.data.statistics || null;
        this.deliveryStats = data.data.delivery_statistics || null;
        this.unreadCount = data.data.unread_count || 0;
        this.recent = data.data.recent || [];
        this.channelCatalog = data.data.channels || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load notification dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCenter() {
      try {
        const { data } = await notificationService.center();
        this.unreadCount = data.data.unread_count || 0;
        this.recent = data.data.recent || [];
        this.channels = data.data.channels || {};
        this.channelCatalog = data.data.channel_catalog || [];
        this.events = data.data.events || [];
        this.statuses = data.data.statuses || [];
        this.priorities = data.data.priorities || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error);
        throw error;
      }
    },

    async fetchUnreadCount() {
      try {
        const { data } = await notificationService.unreadCount();
        this.unreadCount = data.data.unread_count || 0;
        return this.unreadCount;
      } catch {
        return this.unreadCount;
      }
    },

    async fetchList(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await notificationService.list(params);
        this.items = data.data.notifications.items || [];
        this.meta = data.data.notifications.meta || this.meta;
        this.unreadCount = data.data.unread_count ?? this.unreadCount;
        return data.data.notifications;
      } catch (error) {
        this.error = extractError(error, 'Unable to load notifications');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUnread(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await notificationService.unread(params);
        this.items = data.data.notifications.items || [];
        this.meta = data.data.notifications.meta || this.meta;
        this.unreadCount = data.data.unread_count ?? this.unreadCount;
        return data.data.notifications;
      } catch (error) {
        this.error = extractError(error, 'Unable to load unread notifications');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async markRead(id) {
      const { data } = await notificationService.markRead(id);
      this.unreadCount = data.data.unread_count ?? this.unreadCount;
      const updated = data.data.notification;
      this.items = this.items.map((item) =>
        item.uuid === id || item.id === id
          ? { ...item, is_read: true, read_at: updated.read_at }
          : item
      );
      this.recent = this.recent.map((item) =>
        item.uuid === id || item.id === id ? { ...item, is_read: true } : item
      );
      return updated;
    },

    async markAllRead() {
      const { data } = await notificationService.markAllRead();
      this.unreadCount = 0;
      this.items = this.items.map((item) => ({ ...item, is_read: true }));
      this.recent = this.recent.map((item) => ({ ...item, is_read: true }));
      return data.data;
    },

    async fetchPreferences() {
      this.loading = true;
      try {
        const { data } = await notificationService.preferences();
        this.preferences = data.data.preferences || [];
        return this.preferences;
      } catch (error) {
        this.error = extractError(error, 'Unable to load preferences');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async savePreferences(preferences) {
      this.saving = true;
      try {
        const { data } = await notificationService.syncPreferences(preferences);
        this.preferences = data.data.preferences || [];
        return this.preferences;
      } catch (error) {
        this.error = extractError(error, 'Unable to save preferences');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchTemplates(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await notificationService.templates(params);
        this.templates = data.data.templates.items || [];
        this.templateMeta = data.data.templates.meta || this.templateMeta;
        this.templateEvents = data.data.events || [];
        this.templateChannels = data.data.channels || [];
        this.templateLocales = data.data.locales || [];
        this.templateWorkflowStatuses = data.data.workflow_statuses || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load templates');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchTemplate(id) {
      this.loading = true;
      try {
        const { data } = await notificationService.template(id);
        this.currentTemplate = data.data.template;
        return data.data.template;
      } catch (error) {
        this.error = extractError(error, 'Unable to load template');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async saveTemplate(payload, id = null) {
      this.saving = true;
      this.error = null;
      try {
        if (id) {
          const { data } = await notificationService.updateTemplate(id, payload);
          this.currentTemplate = data.data.template;
          return data.data.template;
        }
        const { data } = await notificationService.createTemplate(payload);
        this.currentTemplate = data.data.template;
        return data.data.template;
      } catch (error) {
        this.error = extractError(error, 'Unable to save template');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async removeTemplate(id) {
      await notificationService.deleteTemplate(id);
    },

    async previewTemplate(id, variables = {}) {
      const { data } = await notificationService.previewTemplate(id, { variables });
      this.templatePreview = data.data.preview;
      return data.data.preview;
    },

    async testSendTemplate(id, payload = {}) {
      this.saving = true;
      try {
        const { data } = await notificationService.testSendTemplate(id, payload);
        this.successMessage = data.message || 'Test notification sent.';
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to send test notification');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async submitTemplate(id, payload = {}) {
      const { data } = await notificationService.submitTemplate(id, payload);
      this.currentTemplate = data.data.template;
      this.successMessage = data.message || 'Submitted for review.';
      return data.data.template;
    },

    async publishTemplate(id) {
      const { data } = await notificationService.publishTemplate(id);
      this.currentTemplate = data.data.template;
      this.successMessage = data.message || 'Template published.';
      return data.data.template;
    },

    async fetchTemplateVersions(id) {
      this.loading = true;
      try {
        const { data } = await notificationService.templateVersions(id);
        this.templateVersions = data.data.versions || [];
        this.templateVersionMeta = data.data.template || null;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load versions');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async compareTemplateVersions(id, from, to) {
      const { data } = await notificationService.compareTemplateVersions(id, from, to);
      return data.data;
    },

    async restoreTemplateVersion(id, version, payload = {}) {
      const { data } = await notificationService.restoreTemplateVersion(id, version, payload);
      this.currentTemplate = data.data.template;
      this.successMessage = data.message || 'Version restored.';
      return data.data.template;
    },

    async fetchTemplateApprovals(params = {}) {
      this.loading = true;
      try {
        const { data } = await notificationService.templateApprovals(params);
        this.templateApprovals = data.data.approvals.items || [];
        this.templateApprovalMeta = data.data.approvals.meta || this.templateApprovalMeta;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load approvals');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async approveTemplate(approvalId, payload = {}) {
      const { data } = await notificationService.approveTemplate(approvalId, payload);
      this.successMessage = data.message || 'Template approved.';
      return data.data.template;
    },

    async rejectTemplate(approvalId, payload = {}) {
      const { data } = await notificationService.rejectTemplate(approvalId, payload);
      this.successMessage = data.message || 'Template rejected.';
      return data.data.template;
    },

    async fetchDeliveryLogs(params = {}) {
      this.loading = true;
      try {
        const { data } = await notificationService.deliveryLogs(params);
        this.logs = data.data.logs.items || [];
        this.logMeta = data.data.logs.meta || this.logMeta;
        this.logStats = data.data.statistics || null;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load delivery logs');
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
