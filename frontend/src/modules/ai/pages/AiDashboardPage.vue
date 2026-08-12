<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'ai.conversations' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Open chat
      </RouterLink>
      <RouterLink
        :to="{ name: 'ai.settings' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        AI settings
      </RouterLink>
    </Teleport>

    <AiSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <section class="mb-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
      <h2 class="mb-4 text-base font-semibold text-slate-900">Enabled features</h2>
      <div v-if="store.loading && !(store.catalog.features || []).length" class="flex flex-wrap gap-2">
        <div v-for="n in 8" :key="n" class="h-7 w-28 animate-pulse rounded-full bg-zinc-100" />
      </div>
      <div v-else class="flex flex-wrap gap-2">
        <span
          v-for="feature in store.catalog.features || []"
          :key="feature.value"
          class="rounded-full px-3 py-1 text-xs font-medium"
          :class="feature.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-slate-500'"
        >
          {{ feature.label }}
        </span>
        <p
          v-if="!(store.catalog.features || []).length"
          class="py-2 text-sm text-slate-500"
        >
          No features configured.
        </p>
      </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Recent conversations</h2>
          <RouterLink
            :to="{ name: 'ai.conversations' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View all
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recentConversations.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <p v-else-if="!store.recentConversations.length" class="py-10 text-center text-sm text-slate-500">
          No conversations yet.
        </p>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="item in store.recentConversations"
            :key="item.uuid"
            class="py-3.5 first:pt-0 last:pb-0"
          >
            <p class="truncate text-sm font-medium text-slate-900">
              {{ item.title || 'Untitled conversation' }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ item.feature_label || item.feature }} · {{ item.status }}
            </p>
          </li>
        </ul>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Recent AI logs</h2>
          <RouterLink
            :to="{ name: 'ai.logs' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View logs
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recentLogs.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <p v-else-if="!store.recentLogs.length" class="py-10 text-center text-sm text-slate-500">
          No usage logs yet.
        </p>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="log in store.recentLogs"
            :key="log.uuid"
            class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-slate-900">
                {{ log.operation }} · {{ log.driver || 'n/a' }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">{{ log.feature_label || log.feature }}</p>
            </div>
            <span
              class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
              :class="log.status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
            >
              {{ log.status }}
            </span>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ChatBubbleLeftRightIcon,
  CpuChipIcon,
  DocumentTextIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();

const hasStats = computed(
  () =>
    store.providerStatistics != null
    || store.promptStatistics != null
    || store.conversationStatistics != null
    || store.usageStatistics != null,
);

const cards = computed(() => [
  {
    label: 'Providers',
    value: store.providerStatistics?.total ?? 0,
    icon: CpuChipIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Prompts',
    value: store.promptStatistics?.total ?? 0,
    icon: DocumentTextIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
  {
    label: 'Conversations',
    value: store.conversationStatistics?.total ?? 0,
    icon: ChatBubbleLeftRightIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-500',
  },
  {
    label: 'Requests (all-time)',
    value: store.usageStatistics?.total ?? 0,
    icon: SparklesIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
]);

onMounted(() => {
  store.fetchDashboard();
});
</script>
