<template>
  <div>
    <PageHeader title="Notification Center" description="Your latest in-app alerts across the AMS platform.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          :disabled="!store.unreadCount"
          @click="markAll"
        >
          Mark all read
        </button>
        <RouterLink
          :to="{ name: 'notifications.preferences' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Preferences
        </RouterLink>
      </template>
    </PageHeader>

    <NotificationsSubnav />

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs uppercase tracking-wide text-slate-500">Unread</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ store.unreadCount }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
        <p class="mt-1 text-sm font-medium text-slate-900">{{ store.channels?.email ? 'Enabled' : 'Disabled' }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs uppercase tracking-wide text-slate-500">In-App</p>
        <p class="mt-1 text-sm font-medium text-slate-900">{{ store.channels?.in_app ? 'Enabled' : 'Disabled' }}</p>
      </div>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div
      v-else-if="store.recent.length === 0"
      class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
    >
      No notifications yet.
    </div>

    <div v-else class="space-y-2">
      <button
        v-for="item in store.recent"
        :key="item.uuid"
        type="button"
        class="flex w-full items-start justify-between gap-3 rounded-xl border px-4 py-3 text-left transition hover:bg-slate-50"
        :class="item.is_read ? 'border-slate-200 bg-white' : 'border-brand-200 bg-brand-50/40'"
        @click="openItem(item)"
      >
        <div>
          <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
          <p class="mt-0.5 text-sm text-slate-600">{{ item.message || item.body }}</p>
          <p class="mt-1 text-xs text-slate-400">{{ formatDate(item.created_at) }}</p>
        </div>
        <span v-if="!item.is_read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-600" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const router = useRouter();

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
