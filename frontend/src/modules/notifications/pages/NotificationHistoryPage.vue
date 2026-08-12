<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="store.loading"
        @click="markAll"
      >
        Mark all read
      </button>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search notifications…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.unread"
              wrapper-class="min-w-[8.5rem]"
              :options="unreadOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.channel"
              wrapper-class="min-w-[9.5rem]"
              :options="channelOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.priority"
              wrapper-class="min-w-[9.5rem]"
              :options="priorityOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.items.length"
        title="No notifications found"
        description="Try adjusting your filters or wait for new notifications."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Notification</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Channel</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Priority</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.items"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="max-w-md px-5 py-4">
                <div class="flex items-start gap-2">
                  <span
                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                    :class="item.is_read ? 'bg-zinc-300' : 'bg-brand-600'"
                  />
                  <div class="min-w-0">
                    <p class="font-medium text-slate-900">{{ item.title }}</p>
                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">
                      {{ plainText(item.message || item.body) }}
                    </p>
                    <p class="mt-1 font-mono text-[11px] text-slate-400">
                      {{ item.event_key || '—' }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4">
                <span class="inline-flex rounded-[8px] bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                  {{ item.channel_label || item.channel }}
                </span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="priorityClass(item.priority)"
                >
                  {{ item.priority_label || item.priority }}
                </span>
              </td>
              <td class="px-5 py-4">
                <button
                  v-if="!item.is_read && item.channel === 'in_app'"
                  type="button"
                  class="text-sm font-medium text-brand-700 hover:underline"
                  @click="markOne(item.uuid)"
                >
                  Mark read
                </button>
                <span
                  v-else
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="statusClass(item)"
                >
                  {{ statusLabel(item) }}
                </span>
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                {{ formatDate(item.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
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
import { onMounted, reactive } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();

const filters = reactive({
  unread: '',
  search: '',
  channel: '',
  priority: '',
  page: 1,
  per_page: 20,
});

const unreadOptions = [
  { value: '', label: 'All' },
  { value: '1', label: 'Unread' },
];

const channelOptions = [
  { value: '', label: 'All channels' },
  { value: 'in_app', label: 'In-App' },
  { value: 'email', label: 'Email' },
  { value: 'push', label: 'Push' },
  { value: 'sms', label: 'SMS' },
  { value: 'webhook', label: 'Webhook' },
];

const priorityOptions = [
  { value: '', label: 'All priorities' },
  { value: 'low', label: 'Low' },
  { value: 'normal', label: 'Normal' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
];

onMounted(reload);

async function reload() {
  await store.fetchList({
    unread: filters.unread || undefined,
    search: filters.search || undefined,
    channel: filters.channel || undefined,
    priority: filters.priority || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  reload();
}

function resetFilters() {
  filters.unread = '';
  filters.search = '';
  filters.channel = '';
  filters.priority = '';
  filters.page = 1;
  reload();
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

async function markAll() {
  await store.markAllRead();
  await reload();
}

async function markOne(uuid) {
  await store.markRead(uuid);
  await reload();
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

function statusClass(item) {
  if (item.is_read) return 'bg-zinc-100 text-slate-500';
  if (item.status === 'sent') return 'bg-emerald-50 text-emerald-700';
  if (item.status === 'failed') return 'bg-rose-50 text-rose-700';
  return 'bg-zinc-100 text-slate-600';
}

function statusLabel(item) {
  if (item.is_read) return 'Read';
  return item.status_label || item.status || 'Unread';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
