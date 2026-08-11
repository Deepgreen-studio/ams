<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="!store.unreadCount"
        @click="markAll"
      >
        Mark all read
      </button>
    </Teleport>

    <NotificationsSubnav />

    <div v-if="store.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="store.items.length === 0"
      class="rounded-[12px] border border-dashed border-zinc-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
    >
      You are all caught up.
    </div>

    <div v-else class="space-y-2">
      <button
        v-for="item in store.items"
        :key="item.uuid"
        type="button"
        class="flex w-full items-start justify-between gap-3 rounded-[12px] bg-brand-50/40 px-5 py-4 text-left ring-1 ring-brand-200 transition hover:ring-brand-300"
        @click="openItem(item)"
      >
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
          <p class="mt-0.5 text-sm text-slate-600">{{ item.message || item.body }}</p>
          <p class="mt-1 text-xs text-slate-400">{{ formatDate(item.created_at) }}</p>
        </div>
        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-600" />
      </button>
    </div>

    <div class="mt-4">
      <Pagination :meta="store.meta" @change="onPageChange" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import Pagination from '@/modules/users/components/Pagination.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const router = useRouter();
const filters = reactive({ page: 1, per_page: 20 });

onMounted(() => reload());

async function reload() {
  await store.fetchUnread(filters);
}

async function markAll() {
  await store.markAllRead();
  await reload();
}

async function openItem(item) {
  if (!item.is_read) {
    await store.markRead(item.uuid);
  }
  const ticketUuid = item.data?.ticket_uuid;
  if (ticketUuid) {
    await router.push({ name: 'support.tickets.show', params: { id: ticketUuid } });
  }
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
