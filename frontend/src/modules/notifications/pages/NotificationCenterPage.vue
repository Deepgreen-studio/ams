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
      <RouterLink
        :to="{ name: 'notifications.preferences' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Preferences
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="store.recent.length === 0"
      class="rounded-[12px] border border-dashed border-zinc-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
    >
      No notifications yet.
    </div>

    <div v-else class="space-y-2">
      <button
        v-for="item in store.recent"
        :key="item.uuid"
        type="button"
        class="flex w-full items-start justify-between gap-3 rounded-[12px] px-5 py-4 text-left ring-1 transition hover:ring-brand-200"
        :class="item.is_read ? 'bg-white ring-zinc-100' : 'bg-brand-50/40 ring-brand-200'"
        @click="openItem(item)"
      >
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
          <p class="mt-0.5 text-sm text-slate-600">{{ item.message || item.body }}</p>
          <p class="mt-1 text-xs text-slate-400">{{ formatDate(item.created_at) }}</p>
        </div>
        <span v-if="!item.is_read" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-600" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const router = useRouter();

const summaryCards = computed(() => [
  { label: 'Unread', value: store.unreadCount },
  { label: 'Email', value: store.channels?.email ? 'Enabled' : 'Disabled' },
  { label: 'In-App', value: store.channels?.in_app ? 'Enabled' : 'Disabled' },
]);

onMounted(() => store.fetchCenter());

async function markAll() {
  await store.markAllRead();
  await store.fetchCenter();
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

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
