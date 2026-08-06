<template>
  <div class="rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Conversation</h3>
        <p class="mt-0.5 text-xs text-slate-500">
          {{ messages.length }} message{{ messages.length === 1 ? '' : 's' }}
          <span v-if="unreadCount > 0" class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">
            {{ unreadCount }} unread
          </span>
        </p>
      </div>
      <button
        v-if="unreadCount > 0"
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
        :disabled="markingRead"
        @click="markAllRead"
      >
        Mark all read
      </button>
    </div>

    <div v-if="loading" class="space-y-3 p-5">
      <div class="h-20 animate-pulse rounded-lg bg-slate-100" />
      <div class="h-20 animate-pulse rounded-lg bg-slate-100" />
    </div>

    <div v-else-if="messages.length === 0" class="px-5 py-10 text-center text-sm text-slate-500">
      No messages yet. Post the first reply below.
    </div>

    <div v-else class="max-h-[36rem] space-y-4 overflow-y-auto px-5 py-4">
      <article
        v-for="message in messages"
        :key="message.uuid"
        class="rounded-xl border p-4"
        :class="messageTone(message)"
      >
        <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
          <div>
            <p class="text-sm font-semibold text-slate-900">
              {{ message.author?.full_name || message.author_type_label || 'System' }}
            </p>
            <p class="text-xs text-slate-500">
              {{ formatDate(message.created_at) }}
              · {{ message.visibility_label || message.visibility }}
              · {{ message.author_type_label || message.author_type }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span
              class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
              :class="visibilityBadge(message.visibility)"
            >
              {{ message.visibility }}
            </span>
            <span
              class="rounded-full px-2 py-0.5 text-[10px] font-medium"
              :class="message.is_read ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
            >
              {{ message.is_read ? 'Read' : 'Unread' }}
            </span>
          </div>
        </div>

        <div
          class="prose prose-sm max-w-none text-slate-800"
          v-html="sanitize(message.body)"
        />

        <div v-if="message.attachments?.length" class="mt-3 space-y-2">
          <AttachmentCard
            v-for="attachment in message.attachments"
            :key="attachment.uuid"
            :ticket-id="ticketId"
            :attachment="attachment"
          />
        </div>
      </article>
    </div>

    <div class="border-t border-slate-200 px-5 py-4">
      <h4 class="mb-3 text-sm font-semibold text-slate-900">Reply</h4>

      <div class="mb-3 flex flex-wrap gap-2">
        <label
          v-for="option in visibilityOptions"
          :key="option.value"
          class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium"
          :class="visibility === option.value
            ? 'border-brand-300 bg-brand-50 text-brand-800'
            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
        >
          <input v-model="visibility" type="radio" class="sr-only" :value="option.value" />
          {{ option.label }}
        </label>
      </div>

      <div class="mb-3 flex flex-wrap items-center gap-2">
        <label class="text-xs font-medium text-slate-600">Canned response</label>
        <select
          v-model="selectedCannedId"
          class="min-w-[16rem] flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
          :disabled="cannedLoading || saving"
        >
          <option value="">Insert a template…</option>
          <optgroup v-if="personalCanned.length" label="Personal">
            <option v-for="item in personalCanned" :key="item.uuid" :value="item.uuid">
              {{ item.title }}{{ item.shortcut ? ` (/${item.shortcut})` : '' }}
            </option>
          </optgroup>
          <optgroup v-if="sharedCanned.length" label="Shared">
            <option v-for="item in sharedCanned" :key="item.uuid" :value="item.uuid">
              {{ item.title }}{{ item.shortcut ? ` (/${item.shortcut})` : '' }}
            </option>
          </optgroup>
        </select>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="!selectedCannedId || applyingCanned || saving"
          @click="applyCanned"
        >
          {{ applyingCanned ? 'Inserting…' : 'Insert' }}
        </button>
      </div>

      <TicketReplyEditor v-model="body" :editable="!saving" />

      <div class="mt-3">
        <label class="mb-1 block text-xs font-medium text-slate-600">Attachments</label>
        <input
          ref="fileInput"
          type="file"
          multiple
          class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-200"
          accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.mp4,.webm,.mov"
          @change="onFilesSelected"
        />
        <ul v-if="pendingFiles.length" class="mt-2 space-y-1">
          <li
            v-for="(file, index) in pendingFiles"
            :key="`${file.name}-${index}`"
            class="flex items-center justify-between rounded-md bg-slate-50 px-2 py-1 text-xs text-slate-700"
          >
            <span class="truncate">{{ file.name }} ({{ formatSize(file.size) }})</span>
            <button type="button" class="ml-2 text-rose-600 hover:underline" @click="removeFile(index)">
              Remove
            </button>
          </li>
        </ul>
      </div>

      <p v-if="localError" class="mt-2 text-sm text-rose-600">{{ localError }}</p>

      <div class="mt-4 flex justify-end">
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="saving || !canSubmit"
          @click="submit"
        >
          {{ saving ? 'Sending…' : 'Send reply' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DOMPurify from 'dompurify';
import AttachmentCard from '@/modules/support/components/AttachmentCard.vue';
import TicketReplyEditor from '@/modules/support/components/TicketReplyEditor.vue';
import { useCannedResponsesStore } from '@/modules/support/stores/cannedResponses';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const props = defineProps({
  ticketId: { type: String, required: true },
});

const store = useSupportTicketsStore();
const cannedStore = useCannedResponsesStore();
const body = ref('');
const visibility = ref('public');
const pendingFiles = ref([]);
const fileInput = ref(null);
const localError = ref('');
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

const canSubmit = computed(() => {
  const plain = body.value.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
  return plain.length > 0 || pendingFiles.value.length > 0;
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
  localError.value = '';
  try {
    await store.fetchMessages(props.ticketId);
  } catch (err) {
    localError.value = err?.message || 'Unable to load conversation';
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

async function applyCanned() {
  if (!selectedCannedId.value) return;
  applyingCanned.value = true;
  localError.value = '';
  try {
    const used = await cannedStore.use(selectedCannedId.value);
    const html = used?.body || '';
    const empty = !body.value || body.value === '<p></p>';
    body.value = empty ? html : `${body.value}${html}`;
    selectedCannedId.value = '';
  } catch (err) {
    localError.value = err?.response?.data?.message || err?.message || 'Unable to insert canned response';
  } finally {
    applyingCanned.value = false;
  }
}

function sanitize(html) {
  return DOMPurify.sanitize(html || '', {
    USE_PROFILES: { html: true },
  });
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatSize(bytes) {
  const value = Number(bytes || 0);
  if (value < 1024) return `${value} B`;
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
  return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

function messageTone(message) {
  if (message.visibility === 'internal') {
    return 'border-amber-200 bg-amber-50/60';
  }
  if (message.visibility === 'private') {
    return 'border-violet-200 bg-violet-50/40';
  }
  return 'border-slate-200 bg-white';
}

function visibilityBadge(visibilityValue) {
  if (visibilityValue === 'internal') return 'bg-amber-100 text-amber-800';
  if (visibilityValue === 'private') return 'bg-violet-100 text-violet-800';
  return 'bg-sky-100 text-sky-800';
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
  localError.value = '';
  if (!canSubmit.value) {
    localError.value = 'Write a message or attach a file.';
    return;
  }

  const formData = new FormData();
  formData.append('body', body.value || '<p></p>');
  formData.append('visibility', visibility.value);
  formData.append('body_format', 'html');
  pendingFiles.value.forEach((file) => {
    formData.append('attachments[]', file);
  });

  try {
    await store.postMessage(props.ticketId, formData);
    body.value = '';
    pendingFiles.value = [];
    visibility.value = 'public';
  } catch (err) {
    localError.value = store.error || err?.message || 'Unable to send reply';
  }
}

async function markAllRead() {
  markingRead.value = true;
  localError.value = '';
  try {
    await store.markMessagesRead(props.ticketId);
  } catch (err) {
    localError.value = err?.message || 'Unable to mark messages as read';
  } finally {
    markingRead.value = false;
  }
}
</script>
