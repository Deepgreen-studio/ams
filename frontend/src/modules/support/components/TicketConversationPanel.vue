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

    <div v-else-if="messages.length === 0" class="px-6 py-10 text-center">
      <p class="text-sm font-medium text-slate-900">No messages yet</p>
      <p class="mt-1 text-xs text-slate-500">Post the first reply below.</p>
    </div>

    <div v-else class="max-h-[36rem] space-y-4 overflow-y-auto px-6 py-5">
      <article
        v-for="message in messages"
        :key="message.uuid"
        class="rounded-[12px] p-4 ring-1"
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

    <div class="border-t border-zinc-100 px-6 py-5">
      <h4 class="mb-3 text-sm font-semibold text-slate-900">Reply</h4>

      <div class="mb-3 flex flex-wrap gap-2">
        <label
          v-for="option in visibilityOptions"
          :key="option.value"
          class="inline-flex cursor-pointer items-center gap-2 rounded-[12px] px-3 py-2 text-xs font-medium ring-1"
          :class="visibility === option.value
            ? 'bg-brand-50 text-brand-800 ring-brand-200'
            : 'bg-white text-slate-600 ring-zinc-200 hover:bg-zinc-50'"
        >
          <input v-model="visibility" type="radio" class="sr-only" :value="option.value" />
          {{ option.label }}
        </label>
      </div>

      <div class="mb-3 flex flex-wrap items-center gap-2">
        <SelectBox
          v-model="selectedCannedId"
          wrapper-class="min-w-[16rem] flex-1"
          placeholder="Insert a canned response…"
          :options="cannedSelectOptions"
          :disabled="cannedLoading || saving"
        />
        <button
          type="button"
          class="h-10 rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="!selectedCannedId || applyingCanned || saving"
          @click="applyCanned"
        >
          {{ applyingCanned ? 'Inserting…' : 'Insert' }}
        </button>
      </div>

      <TicketReplyEditor v-model="body" :editable="!saving" />

      <div class="mt-3">
        <label class="mb-1.5 block text-xs font-medium text-slate-600">Attachments</label>
        <input
          ref="fileInput"
          type="file"
          multiple
          class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-[12px] file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-zinc-200"
          accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.mp4,.webm,.mov"
          @change="onFilesSelected"
        />
        <ul v-if="pendingFiles.length" class="mt-2 space-y-1">
          <li
            v-for="(file, index) in pendingFiles"
            :key="`${file.name}-${index}`"
            class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3 py-1.5 text-xs text-slate-700"
          >
            <span class="truncate">{{ file.name }} ({{ formatSize(file.size) }})</span>
            <button type="button" class="ml-2 font-medium text-rose-600 hover:underline" @click="removeFile(index)">
              Remove
            </button>
          </li>
        </ul>
      </div>

      <p v-if="localError" class="mt-2 text-sm text-rose-600">{{ localError }}</p>

      <div class="mt-4 flex justify-end">
        <button
          type="button"
          class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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
import SelectBox from '@/modules/users/components/SelectBox.vue';
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
    return 'bg-amber-50/60 ring-amber-100';
  }
  if (message.visibility === 'private') {
    return 'bg-violet-50/40 ring-violet-100';
  }
  return 'bg-zinc-50/60 ring-zinc-100';
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
