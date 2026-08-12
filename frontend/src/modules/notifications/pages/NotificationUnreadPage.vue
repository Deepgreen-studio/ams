<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="!store.unreadCount || store.loading"
        @click="markAll"
      >
        Mark all read
      </button>
      <RouterLink
        :to="{ name: 'notifications.history' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        View history
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <h2 class="text-base font-semibold text-slate-900">Unread inbox</h2>
        <p class="mt-1 text-sm text-slate-500">
          Notifications waiting for your attention. Click an item to open and mark it read.
        </p>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 5" :key="n" class="h-16 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.items.length"
        title="You are all caught up"
        description="No unread notifications right now. Check history for past activity."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'notifications.history' }"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          >
            View history
          </RouterLink>
          <RouterLink
            :to="{ name: 'notifications.center' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Open center
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="divide-y divide-zinc-100">
        <button
          v-for="item in store.items"
          :key="item.uuid"
          type="button"
          class="flex w-full items-start justify-between gap-4 px-6 py-4 text-left transition hover:bg-zinc-50/80 sm:px-8"
          @click="openItem(item)"
        >
          <div class="flex min-w-0 items-start gap-3">
            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-600" />
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                <span
                  v-if="item.channel_label || item.channel"
                  class="inline-flex rounded-[8px] bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"
                >
                  {{ item.channel_label || item.channel }}
                </span>
                <span
                  v-if="item.priority_label || item.priority"
                  class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium"
                  :class="priorityClass(item.priority)"
                >
                  {{ item.priority_label || item.priority }}
                </span>
              </div>
              <p class="mt-1 line-clamp-2 text-sm text-slate-600">
                {{ plainText(item.message || item.body) }}
              </p>
              <p class="mt-1 text-xs text-slate-400">{{ formatDate(item.created_at) }}</p>
            </div>
          </div>
          <span class="mt-1 shrink-0 text-xs font-medium text-brand-700">Open</span>
        </button>
      </div>

      <div
        v-if="store.meta?.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination :meta="store.meta" @change="onPageChange" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  BellAlertIcon,
  CheckCircleIcon,
  InboxIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const router = useRouter();
const filters = reactive({ page: 1, per_page: 20 });

const summaryCards = computed(() => [
  {
    label: 'Unread',
    value: store.unreadCount ?? store.meta?.total ?? store.items.length,
    icon: BellAlertIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
  {
    label: 'On this page',
    value: store.items.length,
    icon: InboxIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Status',
    value: (store.unreadCount ?? store.items.length) > 0 ? 'Needs attention' : 'All clear',
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
]);

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
    return;
  }
  await reload();
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

function plainText(value) {
  if (!value) return '—';
  return String(value)
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function priorityClass(priority) {
  if (priority === 'urgent') return 'bg-rose-50 text-rose-700';
  if (priority === 'high') return 'bg-amber-50 text-amber-700';
  if (priority === 'low') return 'bg-zinc-100 text-slate-500';
  return 'bg-zinc-100 text-slate-600';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
