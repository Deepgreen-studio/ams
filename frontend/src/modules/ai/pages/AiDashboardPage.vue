<template>
  <div>
    <!-- <PageHeader
      title="AI Assistant"
      description="Provider-agnostic AI architecture for suggestions, routing, translation, search, and chat."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'ai.conversations' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Open chat
        </RouterLink>
        <RouterLink
          :to="{ name: 'ai.settings' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          AI settings
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'ai.conversations' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Open chat
        </RouterLink>
        <RouterLink
          :to="{ name: 'ai.settings' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          AI settings
        </RouterLink>
    </Teleport>

    <AiSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-3 text-sm font-semibold text-slate-900">Enabled features</h2>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="feature in store.catalog.features || []"
          :key="feature.value"
          class="rounded-full px-3 py-1 text-xs font-medium"
          :class="feature.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
        >
          {{ feature.label }}
        </span>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent conversations</h2>
          <RouterLink :to="{ name: 'ai.conversations' }" class="text-xs font-medium text-brand-700 hover:underline">
            View all
          </RouterLink>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-if="!store.recentConversations.length" class="py-6 text-center text-sm text-slate-500">No conversations yet.</li>
          <li v-for="item in store.recentConversations" :key="item.uuid" class="py-3">
            <p class="text-sm font-medium text-slate-900">{{ item.title || 'Untitled conversation' }}</p>
            <p class="text-xs text-slate-500">{{ item.feature_label || item.feature }} · {{ item.status }}</p>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent AI logs</h2>
          <RouterLink :to="{ name: 'ai.logs' }" class="text-xs font-medium text-brand-700 hover:underline">
            View logs
          </RouterLink>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-if="!store.recentLogs.length" class="py-6 text-center text-sm text-slate-500">No usage logs yet.</li>
          <li v-for="log in store.recentLogs" :key="log.uuid" class="flex items-center justify-between gap-3 py-3">
            <div>
              <p class="text-sm font-medium text-slate-900">{{ log.operation }} · {{ log.driver || 'n/a' }}</p>
              <p class="text-xs text-slate-500">{{ log.feature_label || log.feature }}</p>
            </div>
            <span
              class="rounded-full px-2.5 py-1 text-xs font-medium"
              :class="log.status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
            >
              {{ log.status }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();

const cards = computed(() => [
  { label: 'Providers', value: store.providerStatistics?.total ?? 0 },
  { label: 'Prompts', value: store.promptStatistics?.total ?? 0 },
  { label: 'Conversations', value: store.conversationStatistics?.total ?? 0 },
  { label: 'Requests (all-time)', value: store.usageStatistics?.total ?? 0 },
]);

onMounted(() => {
  store.fetchDashboard();
});
</script>
