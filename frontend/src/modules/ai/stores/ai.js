import { defineStore } from 'pinia';
import { aiService } from '@/modules/ai/services/aiService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

const defaultMeta = { current_page: 1, last_page: 1, per_page: 20, total: 0 };

export const useAiStore = defineStore('ai', {
  state: () => ({
    providers: [],
    providerMeta: { ...defaultMeta },
    currentProvider: null,
    prompts: [],
    promptMeta: { ...defaultMeta },
    currentPrompt: null,
    conversations: [],
    conversationMeta: { ...defaultMeta },
    currentConversation: null,
    logs: [],
    logMeta: { ...defaultMeta },
    catalog: { drivers: [], features: [], prompt_statuses: [], registered_drivers: [], config: {} },
    providerStatistics: null,
    promptStatistics: null,
    conversationStatistics: null,
    usageStatistics: null,
    usageAnalytics: null,
    recentLogs: [],
    recentConversations: [],
    settings: null,
    chatReply: null,
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
        const { data } = await aiService.dashboard();
        this.providerStatistics = data.data.provider_statistics || null;
        this.promptStatistics = data.data.prompt_statistics || null;
        this.conversationStatistics = data.data.conversation_statistics || null;
        this.usageStatistics = data.data.usage_statistics || null;
        this.usageAnalytics = data.data.usage_analytics || null;
        this.catalog = data.data.catalog || this.catalog;
        this.recentLogs = data.data.recent_logs || [];
        this.recentConversations = data.data.recent_conversations || [];
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load AI dashboard');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCatalog() {
      const { data } = await aiService.catalog();
      this.catalog = data.data.catalog || this.catalog;
      return this.catalog;
    },

    async fetchAnalytics(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.analytics(params);
        this.usageAnalytics = data.data.analytics || null;
        this.usageStatistics = data.data.usage_statistics || null;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load AI analytics');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchSettings() {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.settings();
        this.settings = data.data;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load AI settings');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async saveSettings(payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.updateSettings(payload);
        this.settings = data.data;
        this.successMessage = data.message || 'Settings saved';
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to save AI settings');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchProviders(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.providers(params);
        this.providers = data.data.providers?.items || [];
        this.providerMeta = data.data.providers?.meta || { ...defaultMeta };
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load AI providers');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createProvider(payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.createProvider(payload);
        this.successMessage = data.message || 'Provider created';
        return data.data.provider;
      } catch (error) {
        this.error = extractError(error, 'Unable to create provider');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async updateProvider(id, payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.updateProvider(id, payload);
        this.successMessage = data.message || 'Provider updated';
        return data.data.provider;
      } catch (error) {
        this.error = extractError(error, 'Unable to update provider');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async deleteProvider(id) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.deleteProvider(id);
        this.successMessage = data.message || 'Provider deleted';
      } catch (error) {
        this.error = extractError(error, 'Unable to delete provider');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async testProvider(id) {
      const { data } = await aiService.testProvider(id);
      return data.data.result;
    },

    async fetchPrompts(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.prompts(params);
        this.prompts = data.data.prompts?.items || [];
        this.promptMeta = data.data.prompts?.meta || { ...defaultMeta };
        this.catalog = data.data.catalog || this.catalog;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load prompts');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createPrompt(payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.createPrompt(payload);
        this.successMessage = data.message || 'Prompt created';
        return data.data.prompt;
      } catch (error) {
        this.error = extractError(error, 'Unable to create prompt');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async updatePrompt(id, payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.updatePrompt(id, payload);
        this.successMessage = data.message || 'Prompt updated';
        return data.data.prompt;
      } catch (error) {
        this.error = extractError(error, 'Unable to update prompt');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async publishPrompt(id) {
      const { data } = await aiService.publishPrompt(id);
      this.successMessage = data.message || 'Prompt published';
      return data.data.prompt;
    },

    async deletePrompt(id) {
      const { data } = await aiService.deletePrompt(id);
      this.successMessage = data.message || 'Prompt deleted';
    },

    async fetchConversations(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.conversations(params);
        this.conversations = data.data.conversations?.items || [];
        this.conversationMeta = data.data.conversations?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load conversations');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchConversation(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.showConversation(id);
        this.currentConversation = data.data.conversation;
        return this.currentConversation;
      } catch (error) {
        this.error = extractError(error, 'Unable to load conversation');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async sendChat(payload) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await aiService.chat(payload);
        this.currentConversation = data.data.conversation;
        this.chatReply = data.data.reply;
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to send chat message');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    async fetchLogs(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await aiService.logs(params);
        this.logs = data.data.logs?.items || [];
        this.logMeta = data.data.logs?.meta || { ...defaultMeta };
        return data.data;
      } catch (error) {
        this.error = extractError(error, 'Unable to load AI logs');
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
