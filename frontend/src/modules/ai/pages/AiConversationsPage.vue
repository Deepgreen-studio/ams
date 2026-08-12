<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="startNewChat"
      >
        New chat
      </button>
      <RouterLink
        :to="{ name: 'ai.settings' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        AI settings
      </RouterLink>
    </Teleport>

    <AiSubnav />

    <div
      v-if="usingStubProvider"
      class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
    >
      Conversations are working, but replies come from
      <strong>Local Null Provider</strong> (stub). Add a live provider under
      <RouterLink :to="{ name: 'ai.settings' }" class="font-semibold underline">AI Settings</RouterLink>,
      paste an API key, mark it as <strong>Default</strong>, then Test — or pick it below.
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
      <section class="flex max-h-[40rem] flex-col overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 lg:col-span-2">
        <div class="shrink-0 border-b border-zinc-100 px-5 py-4">
          <div class="relative">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="search"
              type="search"
              placeholder="Search conversations…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="loadList"
            />
          </div>
        </div>
        <ul class="scrollbar-light min-h-0 flex-1 divide-y divide-zinc-100 overflow-y-auto">
          <li v-if="store.loading && !store.conversations.length" class="space-y-3 px-5 py-5">
            <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
          </li>
          <li
            v-else-if="!store.conversations.length"
            class="px-5 py-10 text-center text-sm text-slate-500"
          >
            No conversations.
          </li>
          <li
            v-for="item in store.conversations"
            :key="item.uuid"
            class="cursor-pointer px-5 py-3.5 transition hover:bg-zinc-50"
            :class="selectedUuid === item.uuid ? 'bg-brand-50' : ''"
            @click="openConversation(item.uuid)"
          >
            <div class="flex items-start justify-between gap-2">
              <p class="min-w-0 truncate text-sm font-medium text-slate-900">
                {{ item.title || 'Untitled' }}
              </p>
              <span
                class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                :class="conversationStatusClass(item.status)"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full"
                  :class="conversationStatusDotClass(item.status)"
                />
                {{ item.status }}
              </span>
            </div>
            <p class="mt-0.5 truncate text-xs text-slate-500">
              {{ item.feature_label || item.feature }}
              <span v-if="item.provider?.name"> · {{ item.provider.name }}</span>
            </p>
          </li>
        </ul>
      </section>

      <section class="flex max-h-[40rem] flex-col rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-3">
        <div class="mb-4 flex shrink-0 flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">
              {{ store.currentConversation?.title || 'New chat' }}
            </h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Active provider:
              <span class="font-medium text-slate-700">{{ activeProviderLabel }}</span>
            </p>
          </div>
          <div class="min-w-[12rem]">
            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-slate-500">
              Provider
            </label>
            <SelectBox
              v-model="selectedProviderId"
              :options="providerOptions"
              placeholder="Default provider"
              :disabled="!!selectedUuid"
            />
            <p v-if="selectedUuid" class="mt-1.5 text-[11px] text-slate-500">
              Provider is locked for an existing conversation. Start a new chat to switch.
            </p>
          </div>
        </div>

        <div class="scrollbar-light mb-4 min-h-0 flex-1 space-y-3 overflow-y-auto rounded-[12px] border border-zinc-100 bg-zinc-50 p-4">
          <div
            v-if="!(store.currentConversation?.messages || []).length"
            class="py-10 text-center text-sm text-slate-500"
          >
            Start a conversation below.
          </div>
          <div
            v-for="messageItem in store.currentConversation?.messages || []"
            :key="messageItem.uuid"
            class="rounded-[12px] px-3.5 py-2.5 text-sm"
            :class="messageItem.role === 'user' ? 'bg-white text-slate-800 ring-1 ring-zinc-100' : 'bg-brand-50 text-slate-800'"
          >
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
              {{ messageItem.role }}
            </p>
            <p class="whitespace-pre-wrap">{{ messageItem.content }}</p>
          </div>
        </div>

        <form class="flex shrink-0 gap-2" @submit.prevent="send">
          <input
            v-model="message"
            required
            class="h-10 flex-1 rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="Ask the AI assistant…"
          />
          <button
            type="submit"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving"
          >
            {{ store.saving ? 'Sending…' : 'Send' }}
          </button>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const toast = useToast();
const search = ref('');
const message = ref('');
const selectedUuid = ref(null);
const selectedProviderId = ref('');

const enabledProviders = computed(() =>
  (store.providers || []).filter((provider) => provider.is_enabled)
);

const providerOptions = computed(() => [
  { value: '', label: 'Default provider' },
  ...enabledProviders.value.map((provider) => ({
    value: provider.uuid,
    label: `${provider.name} (${provider.driver_label || provider.driver})`,
  })),
]);

const defaultProvider = computed(() =>
  enabledProviders.value.find((provider) => provider.is_default) || enabledProviders.value[0] || null
);

const activeProvider = computed(() => {
  if (store.currentConversation?.provider) {
    return store.currentConversation.provider;
  }
  if (selectedProviderId.value) {
    return enabledProviders.value.find((provider) => provider.uuid === selectedProviderId.value) || null;
  }
  return defaultProvider.value;
});

const activeProviderLabel = computed(() => {
  if (!activeProvider.value) return 'None configured';
  const name = activeProvider.value.name || 'Provider';
  const driver = activeProvider.value.driver_label || activeProvider.value.driver || '';
  return driver ? `${name} · ${driver}` : name;
});

const usingStubProvider = computed(() => {
  const driver = activeProvider.value?.driver;
  return driver === 'null' || (!activeProvider.value && true);
});

const isQuotaError = computed(() => {
  const text = String(store.error || '').toLowerCase();
  return text.includes('quota') || text.includes('rate limit') || text.includes('429') || text.includes('resource_exhausted');
});

watch(
  () => store.error,
  (err) => {
    if (!err) return;
    const tip = isQuotaError.value
      ? ' Tip: wait ~1 minute, try gemini-flash-latest, or enable billing in Google AI Studio.'
      : '';
    toast.error(`${err}${tip}`, 'Chat request failed');
    store.error = null;
  },
);

function conversationStatusClass(status) {
  if (status === 'active') return 'bg-emerald-50 text-emerald-700';
  if (status === 'archived') return 'bg-zinc-100 text-slate-600';
  return 'bg-zinc-100 text-slate-600';
}

function conversationStatusDotClass(status) {
  if (status === 'active') return 'bg-emerald-500';
  return 'bg-slate-400';
}

function startNewChat() {
  selectedUuid.value = null;
  store.currentConversation = null;
  message.value = '';
  if (defaultProvider.value) {
    selectedProviderId.value = defaultProvider.value.uuid;
  }
}

async function loadList() {
  await store.fetchConversations({ search: search.value || undefined });
}

async function openConversation(uuid) {
  selectedUuid.value = uuid;
  await store.fetchConversation(uuid);
  selectedProviderId.value = store.currentConversation?.provider?.uuid || '';
}

async function send() {
  const payload = {
    message: message.value,
    conversation_id: selectedUuid.value || undefined,
  };
  if (!selectedUuid.value && selectedProviderId.value) {
    payload.provider_id = selectedProviderId.value;
  }
  try {
    const result = await store.sendChat(payload);
    selectedUuid.value = result.conversation.uuid;
    message.value = '';
    await loadList();
  } catch {
    // toast via watcher
  }
}

onMounted(async () => {
  store.error = null;
  await Promise.all([
    loadList(),
    store.fetchProviders({ per_page: 100 }),
  ]);
  if (defaultProvider.value) {
    selectedProviderId.value = defaultProvider.value.uuid;
  }
});
</script>
