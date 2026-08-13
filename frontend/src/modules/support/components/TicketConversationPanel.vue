<template>
  <div class="rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-6 py-5">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Conversation</h3>
        <p class="mt-0.5 text-xs text-slate-500">
          {{ messages.length }} message{{ messages.length === 1 ? '' : 's' }}
          <span v-if="unreadCount > 0" class="ml-2 rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800">
            {{ unreadCount }} unread
          </span>
        </p>
      </div>
      <button
        v-if="unreadCount > 0"
        type="button"
        class="rounded-[12px] border border-zinc-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="markingRead"
        @click="markAllRead"
      >
        Mark all read
      </button>
    </div>

    <div v-if="loading" class="space-y-3 px-6 py-5">
      <div class="h-20 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="h-20 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="scrollbar-light max-h-[40rem] space-y-3 overflow-y-auto bg-zinc-50/80 px-5 py-5">
      <article v-if="openingBody" class="flex justify-start">
        <div class="max-w-[80%]">
          <p class="mb-1 px-1 text-[11px] text-slate-500">Original request</p>
          <div class="w-fit max-w-full rounded-[18px] rounded-bl-md bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-800 ring-1 ring-zinc-100">
            <p class="whitespace-pre-wrap">{{ openingBody }}</p>
          </div>
        </div>
      </article>

      <div v-if="!messages.length && !openingBody" class="py-8 text-center">
        <p class="text-sm font-medium text-slate-900">No messages yet</p>
        <p class="mt-1 text-xs text-slate-500">Post the first reply below.</p>
      </div>

      <article
        v-for="message in messages"
        :key="message.uuid"
        class="flex"
        :class="messageRowClass(message)"
      >
        <div
          v-if="isSystem(message)"
          class="mx-auto max-w-[90%] text-center"
        >
          <p class="mb-1 text-[11px] text-slate-400">{{ formatChatTime(message.created_at) }}</p>
          <div
            class="rounded-full bg-zinc-100 px-3 py-1 text-xs text-slate-600 [&_p]:m-0"
            v-html="sanitize(message.body)"
          />
        </div>

        <div
          v-else-if="message.visibility === 'internal'"
          class="w-full max-w-[92%]"
        >
          <p class="mb-1 text-center text-[11px] text-amber-700">
            Internal note · {{ messageAuthor(message) }} · {{ formatChatTime(message.created_at) }}
          </p>
          <div class="rounded-[12px] bg-amber-50 px-3.5 py-2.5 text-sm leading-6 text-amber-950 ring-1 ring-amber-100">
            <div class="chat-body" v-html="sanitize(message.body)" />
            <div v-if="message.attachments?.length" class="mt-2 space-y-2">
              <AttachmentCard
                v-for="attachment in message.attachments"
                :key="attachment.uuid"
                :ticket-id="ticketId"
                :attachment="attachment"
              />
            </div>
          </div>
        </div>

        <div v-else class="max-w-[80%]" :class="isOutbound(message) ? 'ml-auto' : ''">
          <p
            class="mb-1 px-1 text-[11px] text-slate-500"
            :class="isOutbound(message) ? 'text-right' : 'text-left'"
          >
            {{ messageAuthor(message) }}
            · {{ formatChatTime(message.created_at) }}
            <span v-if="message.visibility === 'private'"> · Private</span>
            <span v-if="!message.is_read"> · Unread</span>
          </p>
          <div class="w-fit max-w-full" :class="[bubbleClass(message), isOutbound(message) ? 'ml-auto' : '']">
            <div class="chat-body" v-html="sanitize(message.body)" />
            <div v-if="message.attachments?.length" class="mt-2 space-y-2">
              <AttachmentCard
                v-for="attachment in message.attachments"
                :key="attachment.uuid"
                :ticket-id="ticketId"
                :attachment="attachment"
              />
            </div>
          </div>
        </div>
      </article>
    </div>

    <div class="border-t border-zinc-100 px-6 py-5">
      <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <h4 class="text-sm font-semibold text-slate-900">Reply</h4>
        <p class="text-xs text-slate-500">{{ visibilityHint }}</p>
      </div>

      <div class="mb-4 border-b border-zinc-200">
        <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Reply type">
          <button
            v-for="option in visibilityOptions"
            :key="option.value"
            type="button"
            class="shrink-0 border-b-2 px-3.5 py-2 text-sm font-medium transition-colors"
            :class="visibility === option.value
              ? 'border-brand-600 text-brand-700'
              : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'"
            @click="visibility = option.value"
          >
            {{ option.label }}
          </button>
        </nav>
      </div>

      <SelectBox
        v-if="cannedSelectOptions.length"
        v-model="selectedCannedId"
        wrapper-class="mb-3"
        placeholder="Insert a canned response…"
        :options="cannedSelectOptions"
        :disabled="cannedLoading || applyingCanned || saving"
        @change="applyCanned"
      />

      <TicketReplyEditor
        v-model="body"
        :placeholder="editorPlaceholder"
        :editable="!saving"
      />

      <ul v-if="pendingFiles.length" class="mt-3 space-y-1.5">
        <li
          v-for="(file, index) in pendingFiles"
          :key="`${file.name}-${index}`"
          class="flex items-center justify-between gap-3 rounded-[12px] bg-zinc-50 px-3 py-2 text-xs text-slate-700 ring-1 ring-zinc-100"
        >
          <span class="min-w-0 truncate">{{ file.name }} · {{ formatSize(file.size) }}</span>
          <button
            type="button"
            class="shrink-0 font-medium text-rose-600 hover:text-rose-700"
            @click="removeFile(index)"
          >
            Remove
          </button>
        </li>
      </ul>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <label
          class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          :class="saving ? 'pointer-events-none opacity-60' : ''"
        >
          <PaperClipIcon class="h-4 w-4 text-slate-500" />
          Attach files
          <input
            ref="fileInput"
            type="file"
            multiple
            class="hidden"
            accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.mp4,.webm,.mov"
            @change="onFilesSelected"
          />
        </label>

        <button
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="saving || !canSubmit"
          @click="submit"
        >
          <PaperAirplaneIcon class="h-4 w-4" />
          {{ submitLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DOMPurify from 'dompurify';
import { PaperAirplaneIcon, PaperClipIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AttachmentCard from '@/modules/support/components/AttachmentCard.vue';
import TicketReplyEditor from '@/modules/support/components/TicketReplyEditor.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useCannedResponsesStore } from '@/modules/support/stores/cannedResponses';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const props = defineProps({
  ticketId: { type: String, required: true },
  openingBody: { type: String, default: '' },
});

const store = useSupportTicketsStore();
const cannedStore = useCannedResponsesStore();
const toast = useToast();
const body = ref('');
const visibility = ref('public');
const pendingFiles = ref([]);
const fileInput = ref(null);
const markingRead = ref(false);
const conversationLoading = ref(false);
const cannedLoading = ref(false);
const applyingCanned = ref(false);
const selectedCannedId = ref('');

const visibilityOptions = [
  { value: 'public', label: 'Public reply' },
  { value: 'private', label: 'Private reply' },
  { value: 'internal', label: 'Internal note' },
];

const messages = computed(() => store.messages);
const unreadCount = computed(() => store.unreadCount);
const saving = computed(() => store.saving);
const loading = computed(() => conversationLoading.value);
const personalCanned = computed(() =>
  (cannedStore.items || []).filter((item) => item.visibility === 'personal' && item.is_active)
);
const sharedCanned = computed(() =>
  (cannedStore.items || []).filter((item) => item.visibility === 'shared' && item.is_active)
);

const cannedSelectOptions = computed(() => [
  ...personalCanned.value.map((item) => ({
    value: item.uuid,
    label: cannedLabel(item),
    group: 'Personal',
  })),
  ...sharedCanned.value.map((item) => ({
    value: item.uuid,
    label: cannedLabel(item),
    group: 'Shared',
  })),
]);

function cannedLabel(item) {
  return `${item.title}${item.shortcut ? ` (/${item.shortcut})` : ''}`;
}

const canSubmit = computed(() => {
  const plain = body.value.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
  return plain.length > 0 || pendingFiles.value.length > 0;
});

const visibilityHint = computed(() => {
  if (visibility.value === 'internal') return 'Visible to your team only';
  if (visibility.value === 'private') return 'Hidden from the customer';
  return 'Visible to the customer';
});

const editorPlaceholder = computed(() => {
  if (visibility.value === 'internal') return 'Write an internal note…';
  if (visibility.value === 'private') return 'Write a private reply…';
  return 'Write a public reply…';
});

const submitLabel = computed(() => {
  if (saving.value) return 'Sending…';
  if (visibility.value === 'internal') return 'Add note';
  if (visibility.value === 'private') return 'Send privately';
  return 'Send reply';
});

onMounted(() => {
  loadConversation();
  loadCannedResponses();
});

watch(
  () => props.ticketId,
  () => {
    loadConversation();
  }
);

async function loadConversation() {
  if (!props.ticketId) return;
  conversationLoading.value = true;
  try {
    await store.fetchMessages(props.ticketId);
  } catch (err) {
    toast.error(err?.message || 'Unable to load conversation');
  } finally {
    conversationLoading.value = false;
  }
}

async function loadCannedResponses() {
  cannedLoading.value = true;
  try {
    await cannedStore.fetchList({ is_active: 1, per_page: 100, sort_by: 'title' });
  } catch {
    // Non-blocking: reply composer still works without templates.
  } finally {
    cannedLoading.value = false;
  }
}

async function applyCanned(id) {
  const cannedId = id || selectedCannedId.value;
  if (!cannedId) return;
  applyingCanned.value = true;
  try {
    const used = await cannedStore.use(cannedId);
    const html = used?.body || '';
    const empty = !body.value || body.value === '<p></p>';
    body.value = empty ? html : `${body.value}${html}`;
    selectedCannedId.value = '';
  } catch (err) {
    toast.error(err?.response?.data?.message || err?.message || 'Unable to insert canned response');
  } finally {
    applyingCanned.value = false;
  }
}

function sanitize(html) {
  return DOMPurify.sanitize(html || '', {
    USE_PROFILES: { html: true },
  });
}

function formatChatTime(value) {
  if (!value) return '';
  const date = new Date(value);
  const sameDay = date.toDateString() === new Date().toDateString();
  return date.toLocaleString(undefined, sameDay
    ? { hour: 'numeric', minute: '2-digit' }
    : { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function formatSize(bytes) {
  const value = Number(bytes || 0);
  if (value < 1024) return `${value} B`;
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
  return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

function isSystem(message) {
  return Boolean(message.is_system) || message.author_type === 'system';
}

function isOutbound(message) {
  return !isSystem(message) && message.author_type !== 'customer';
}

function messageAuthor(message) {
  return message.author?.full_name || message.author_type_label || 'System';
}

function messageRowClass(message) {
  if (isSystem(message) || message.visibility === 'internal') {
    return 'justify-center';
  }
  return isOutbound(message) ? 'justify-end' : 'justify-start';
}

function bubbleClass(message) {
  const base = 'rounded-[18px] px-3.5 py-2.5 text-left text-sm leading-6';
  if (message.visibility === 'private') {
    return `${base} rounded-br-md bg-violet-50 text-violet-950 ring-1 ring-violet-100`;
  }
  if (isOutbound(message)) {
    return `${base} rounded-br-md bg-brand-50 text-slate-800 ring-1 ring-brand-100`;
  }
  return `${base} rounded-bl-md bg-white text-slate-800 ring-1 ring-zinc-100`;
}

function onFilesSelected(event) {
  const files = Array.from(event.target.files || []);
  pendingFiles.value = [...pendingFiles.value, ...files].slice(0, 10);
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function removeFile(index) {
  pendingFiles.value = pendingFiles.value.filter((_, i) => i !== index);
}

async function submit() {
  if (!canSubmit.value) {
    toast.error('Write a message or attach a file.');
    return;
  }

  const sentAs = visibility.value;
  const formData = new FormData();
  formData.append('body', body.value || '<p></p>');
  formData.append('visibility', sentAs);
  formData.append('body_format', 'html');
  pendingFiles.value.forEach((file) => {
    formData.append('attachments[]', file);
  });

  try {
    await store.postMessage(props.ticketId, formData);
    body.value = '';
    pendingFiles.value = [];
    visibility.value = 'public';
    toast.success(sentAs === 'internal' ? 'Note added' : 'Reply sent');
  } catch (err) {
    toast.error(store.error || err?.message || 'Unable to send reply');
  }
}

async function markAllRead() {
  markingRead.value = true;
  try {
    await store.markMessagesRead(props.ticketId);
  } catch (err) {
    toast.error(err?.message || 'Unable to mark messages as read');
  } finally {
    markingRead.value = false;
  }
}
</script>

<style scoped>
.chat-body :deep(p) {
  margin: 0;
}

.chat-body :deep(p + p) {
  margin-top: 0.5rem;
}

.chat-body :deep(ul),
.chat-body :deep(ol) {
  margin: 0.35rem 0 0;
  padding-left: 1.1rem;
}

.chat-body :deep(a) {
  color: #c2410c;
  text-decoration: underline;
}
</style>
