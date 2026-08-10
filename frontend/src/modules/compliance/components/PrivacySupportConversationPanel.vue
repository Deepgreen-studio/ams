<template>
  <div class="rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Linked Support conversation</h3>
        <p class="mt-0.5 text-xs text-slate-500">
          <template v-if="ticket">
            {{ ticket.ticket_number }} · {{ ticket.source || 'api' }}
            <span v-if="ticket.source === 'sms'" class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 font-medium text-amber-800">
              Public reply sends SMS to the app
            </span>
          </template>
          <template v-else>No linked Support ticket</template>
        </p>
      </div>
      <RouterLink
        v-if="ticket?.uuid"
        :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
      >
        Open Support ticket
      </RouterLink>
    </div>

    <div v-if="!ticket" class="px-5 py-8 text-center text-sm text-slate-500">
      This privacy request is not linked to a Support ticket, so SMS/chat reply is unavailable here.
    </div>

    <template v-else>
      <div v-if="loading" class="space-y-3 p-5">
        <div class="h-16 animate-pulse rounded-lg bg-slate-100" />
        <div class="h-16 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div v-else-if="messages.length === 0" class="px-5 py-8 text-center text-sm text-slate-500">
        No messages yet. Post a Public reply to notify the customer on the connected app.
      </div>

      <div v-else class="max-h-[28rem] space-y-3 overflow-y-auto px-5 py-4">
        <article
          v-for="message in messages"
          :key="message.uuid"
          class="rounded-xl border p-3"
          :class="messageTone(message)"
        >
          <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-slate-900">
              {{ message.author?.full_name || message.author_type_label || 'System' }}
            </p>
            <span class="text-xs text-slate-500">
              {{ formatDate(message.created_at) }} · {{ message.visibility }}
            </span>
          </div>
          <div class="prose prose-sm max-w-none text-slate-800" v-html="sanitize(message.body)" />
        </article>
      </div>

      <div class="border-t border-slate-200 px-5 py-4">
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

        <textarea
          v-model="body"
          rows="3"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
          :placeholder="ticket.source === 'sms'
            ? 'Public reply will be sent as SMS via the connected app…'
            : 'Public reply will appear in the app live chat…'"
          :disabled="saving"
        />

        <p v-if="localError" class="mt-2 text-sm text-rose-600">{{ localError }}</p>

        <div class="mt-3 flex justify-end">
          <button
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="saving || !canSubmit"
            @click="submit"
          >
            {{ saving ? 'Sending…' : (visibility === 'public' && ticket.source === 'sms' ? 'Send SMS reply' : 'Send reply') }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import DOMPurify from 'dompurify';
import { privacyRequestService } from '@/modules/compliance/services/privacyRequestService';

const props = defineProps({
  privacyRequestId: { type: String, required: true },
  ticket: { type: Object, default: null },
});

const messages = ref([]);
const loading = ref(false);
const saving = ref(false);
const body = ref('');
const visibility = ref('public');
const localError = ref('');

const visibilityOptions = [
  { value: 'public', label: 'Public reply' },
  { value: 'private', label: 'Private note' },
  { value: 'internal', label: 'Internal note' },
];

const canSubmit = computed(() => body.value.trim().length > 0);

onMounted(loadConversation);
watch(() => props.privacyRequestId, loadConversation);

async function loadConversation() {
  if (!props.privacyRequestId || !props.ticket?.uuid) {
    messages.value = [];
    return;
  }

  loading.value = true;
  localError.value = '';
  try {
    const { data } = await privacyRequestService.conversation(props.privacyRequestId);
    messages.value = data.data?.messages ?? [];
  } catch (err) {
    localError.value = err?.message || 'Unable to load conversation';
  } finally {
    loading.value = false;
  }
}

async function submit() {
  if (!canSubmit.value) return;
  saving.value = true;
  localError.value = '';
  try {
    const html = `<p>${escapeHtml(body.value.trim()).replace(/\n/g, '<br>')}</p>`;
    await privacyRequestService.reply(props.privacyRequestId, {
      body: html,
      body_format: 'html',
      visibility: visibility.value,
    });
    body.value = '';
    await loadConversation();
  } catch (err) {
    localError.value = err?.message || 'Unable to send reply';
  } finally {
    saving.value = false;
  }
}

function messageTone(message) {
  if (message.author_type === 'customer') return 'border-emerald-200 bg-emerald-50/40';
  if (message.visibility === 'internal') return 'border-amber-200 bg-amber-50/50';
  if (message.visibility === 'private') return 'border-violet-200 bg-violet-50/40';
  return 'border-slate-200 bg-white';
}

function sanitize(html) {
  return DOMPurify.sanitize(html || '');
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function escapeHtml(text) {
  return text
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}
</script>
