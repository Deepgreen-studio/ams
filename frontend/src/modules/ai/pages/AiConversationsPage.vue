<template>
  <div>
    <!-- <PageHeader
      title="Conversation History"
      description="Chat assistant history and interactive knowledge conversations."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="startNewChat"
        >
          New chat
        </button>
        <RouterLink
          :to="{ name: 'ai.settings' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          AI settings
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="startNewChat"
        >
          New chat
        </button>
        <RouterLink
          :to="{ name: 'ai.settings' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
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

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
    >
      <p class="font-medium text-rose-900">Chat request failed</p>
      <p class="mt-1 whitespace-pre-wrap break-words">{{ store.error }}</p>
      <p v-if="isQuotaError" class="mt-2 text-xs text-rose-700">
        Tip: wait ~1 minute, change the provider model in AI Settings (try
        <code class="rounded bg-rose-100 px-1">gemini-flash-latest</code>),
        or enable billing in Google AI Studio.
      </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
      <div class="rounded-xl border border-slate-200 bg-white lg:col-span-2">
        <div class="border-b border-slate-200 px-4 py-3">
          <input
            v-model="search"
            type="search"
            placeholder="Search conversations…"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            @keyup.enter="loadList"
          />
        </div>
        <ul class="max-h-[32rem] divide-y divide-slate-100 overflow-y-auto">
          <li v-if="!store.conversations.length" class="px-4 py-8 text-center text-sm text-slate-500">No conversations.</li>
          <li
            v-for="item in store.conversations"
            :key="item.uuid"
            class="cursor-pointer px-4 py-3 hover:bg-slate-50"
            :class="selectedUuid === item.uuid ? 'bg-brand-50' : ''"
            @click="openConversation(item.uuid)"
          >
            <p class="text-sm font-medium text-slate-900">{{ item.title || 'Untitled' }}</p>
            <p class="text-xs text-slate-500">
              {{ item.feature_label || item.feature }} · {{ item.status }}
              <span v-if="item.provider?.name"> · {{ item.provider.name }}</span>
            </p>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-3">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">
              {{ store.currentConversation?.title || 'New chat' }}
            </h2>
            <p class="text-xs text-slate-500">
              Active provider:
              <span class="font-medium text-slate-700">{{ activeProviderLabel }}</span>
            </p>
          </div>
          <div class="min-w-[12rem]">
            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Provider</label>
            <select
              v-model="selectedProviderId"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              :disabled="!!selectedUuid"
            >
              <option value="">Default provider</option>
              <option
                v-for="provider in enabledProviders"
                :key="provider.uuid"
                :value="provider.uuid"
              >
                {{ provider.name }} ({{ provider.driver_label || provider.driver }})
              </option>
            </select>
            <p v-if="selectedUuid" class="mt-1 text-[11px] text-slate-500">
              Provider is locked for an existing conversation. Start a new chat to switch.
            </p>
          </div>
        </div>

        <div class="mb-4 max-h-80 space-y-3 overflow-y-auto rounded-lg border border-slate-100 bg-slate-50 p-4">
          <div v-if="!(store.currentConversation?.messages || []).length" class="py-8 text-center text-sm text-slate-500">
            Start a conversation below.
          </div>
          <div
            v-for="messageItem in store.currentConversation?.messages || []"
            :key="messageItem.uuid"
            class="rounded-lg px-3 py-2 text-sm"
            :class="messageItem.role === 'user' ? 'bg-white text-slate-800' : 'bg-brand-50 text-slate-800'"
          >
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ messageItem.role }}</p>
            <p class="whitespace-pre-wrap">{{ messageItem.content }}</p>
          </div>
        </div>

        <form class="flex gap-2" @submit.prevent="send">
          <input
            v-model="message"
            required
            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
            placeholder="Ask the AI assistant…"
          />
          <button
            type="submit"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving"
          >
            {{ store.saving ? 'Sending…' : 'Send' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const search = ref('');
const message = ref('');
const selectedUuid = ref(null);
const selectedProviderId = ref('');

const enabledProviders = computed(() =>
  (store.providers || []).filter((provider) => provider.is_enabled)
);

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

function startNewChat() {
  selectedUuid.value = null;
  store.currentConversation = null;
  message.value = '';
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
  const result = await store.sendChat(payload);
  selectedUuid.value = result.conversation.uuid;
  message.value = '';
  await loadList();
}

onMounted(async () => {
  await Promise.all([
    loadList(),
    store.fetchProviders({ per_page: 100 }),
  ]);
  if (defaultProvider.value) {
    selectedProviderId.value = defaultProvider.value.uuid;
  }
});
</script>
