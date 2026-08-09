<template>
  <div>
    <!-- <PageHeader title="Notification History" description="Searchable history across your notification inbox.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="store.markAllRead().then(reload)"
        >
          Mark all read
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="store.markAllRead().then(reload)"
        >
          Mark all read
        </button>
    </Teleport>

    <NotificationsSubnav />

    <div class="mb-4 flex flex-wrap gap-2">
      <select v-model="filters.unread" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="onFilterChange">
        <option value="">All</option>
        <option value="1">Unread</option>
      </select>
      <select v-model="filters.channel" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="onFilterChange">
        <option value="">All channels</option>
        <option value="in_app">In-App</option>
        <option value="email">Email</option>
        <option value="push">Push</option>
        <option value="sms">SMS</option>
        <option value="webhook">Webhook</option>
      </select>
      <select v-model="filters.priority" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="onFilterChange">
        <option value="">All priorities</option>
        <option value="low">Low</option>
        <option value="normal">Normal</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search…"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="onFilterChange"
      />
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @click="onFilterChange">
        Search
      </button>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Notification</th>
            <th class="px-4 py-3">Channel</th>
            <th class="px-4 py-3">Priority</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">When</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="!store.items.length">
            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No notifications found.</td>
          </tr>
          <tr v-for="item in store.items" :key="item.uuid" class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.message || item.body }}</p>
              <p class="mt-1 font-mono text-[11px] text-slate-400">{{ item.event_key || '—' }}</p>
            </td>
            <td class="px-4 py-3">{{ item.channel_label || item.channel }}</td>
            <td class="px-4 py-3">{{ item.priority_label || item.priority }}</td>
            <td class="px-4 py-3">
              <button
                v-if="!item.is_read && item.channel === 'in_app'"
                type="button"
                class="text-brand-700 hover:underline"
                @click="store.markRead(item.uuid)"
              >
                Mark read
              </button>
              <span v-else-if="item.is_read" class="text-slate-400">Read</span>
              <span v-else class="text-slate-500">{{ item.status_label || item.status }}</span>
            </td>
            <td class="px-4 py-3 text-slate-500">{{ formatDate(item.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <Pagination :meta="store.meta" @change="onPageChange" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
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

function onFilterChange() {
  filters.page = 1;
  reload();
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
