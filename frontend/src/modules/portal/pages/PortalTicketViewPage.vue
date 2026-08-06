<template>
  <div>
    <RouterLink :to="{ name: 'portal.tickets.index' }" class="text-sm font-medium text-brand-700 hover:underline">
      ← Back to tickets
    </RouterLink>

    <div v-if="store.loading && !ticket" class="mt-4 h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="ticket" class="mt-4 space-y-6">
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ ticket.ticket_number }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ ticket.subject }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ ticket.description }}</p>
          </div>
          <div class="text-right text-sm text-slate-500">
            <p>{{ ticket.status_label || ticket.status }}</p>
            <p>{{ ticket.priority_label || ticket.priority }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Conversation</h2>
        </div>

        <div v-if="store.messages.length === 0" class="px-5 py-8 text-center text-sm text-slate-500">
          No public replies yet.
        </div>

        <div v-else class="max-h-[28rem] space-y-3 overflow-y-auto px-5 py-4">
          <article
            v-for="message in store.messages"
            :key="message.uuid"
            class="rounded-lg border border-slate-200 px-4 py-3"
          >
            <p class="text-sm font-semibold text-slate-900">
              {{ message.author?.full_name || message.author_type_label || 'Support' }}
            </p>
            <p class="text-xs text-slate-500">{{ formatDate(message.created_at) }}</p>
            <div class="prose prose-sm mt-2 max-w-none" v-html="sanitize(message.body)" />
          </article>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
          <label class="mb-1 block text-xs font-medium text-slate-600">Your reply</label>
          <textarea
            v-model="replyBody"
            rows="5"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            placeholder="Write a reply…"
          />
          <p v-if="replyError" class="mt-2 text-sm text-rose-600">{{ replyError }}</p>
          <div class="mt-3 flex justify-end">
            <button
              type="button"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving || !canReply"
              @click="sendReply"
            >
              {{ store.saving ? 'Sending…' : 'Send reply' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DOMPurify from 'dompurify';
import { usePortalSupportStore } from '@/modules/portal/stores/portalSupport';

const store = usePortalSupportStore();
const route = useRoute();
const replyBody = ref('');
const replyError = ref('');

const ticket = computed(() => store.current);
const canReply = computed(() => replyBody.value.trim().length > 0);

onMounted(load);
watch(() => route.params.id, load);

async function load() {
  const id = String(route.params.id || '');
  if (!id) return;
  await store.fetchTicket(id);
  await store.fetchMessages(id);
}

function sanitize(html) {
  return DOMPurify.sanitize(html || '', { USE_PROFILES: { html: true } });
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function sendReply() {
  replyError.value = '';
  if (!canReply.value) return;

  const escaped = replyBody.value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\n/g, '<br>');

  const formData = new FormData();
  formData.append('body', `<p>${escaped}</p>`);
  formData.append('body_format', 'html');

  try {
    await store.reply(String(route.params.id), formData);
    replyBody.value = '';
  } catch (err) {
    replyError.value = err?.response?.data?.message || store.error || 'Unable to send reply';
  }
}
</script>
