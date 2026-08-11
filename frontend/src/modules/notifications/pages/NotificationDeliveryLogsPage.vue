<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="reload"
      >
        Refresh
      </button>
    </Teleport>

    <NotificationsSubnav />

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 rounded-[12px] bg-white p-4 ring-1 ring-zinc-100">
      <select
        v-model="filters.status"
        class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700"
        @change="reload"
      >
        <option value="">All statuses</option>
        <option value="sent">Sent</option>
        <option value="queued">Queued</option>
        <option value="failed">Failed</option>
        <option value="skipped">Skipped</option>
      </select>
      <select
        v-model="filters.channel"
        class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700"
        @change="reload"
      >
        <option value="">All channels</option>
        <option value="email">Email</option>
        <option value="in_app">In-App</option>
      </select>
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="reload"
      >
        Apply
      </button>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full divide-y divide-zinc-100 text-sm">
        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3.5">Event</th>
            <th class="px-5 py-3.5">Channel</th>
            <th class="px-5 py-3.5">Recipient</th>
            <th class="px-5 py-3.5">Status</th>
            <th class="px-5 py-3.5">Subject</th>
            <th class="px-5 py-3.5">When</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-if="!store.logs.length">
            <td colspan="6" class="px-5 py-12 text-center text-slate-500">No delivery logs found.</td>
          </tr>
          <tr v-for="log in store.logs" :key="log.uuid" class="hover:bg-zinc-50/80">
            <td class="px-5 py-4 text-slate-700">{{ log.event_label || log.event_key }}</td>
            <td class="px-5 py-4 text-slate-600">{{ log.channel_label || log.channel }}</td>
            <td class="px-5 py-4 text-slate-600">{{ log.recipient || log.notifiable?.email || '—' }}</td>
            <td class="px-5 py-4">
              <span
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusClass(log.status)"
              >
                {{ log.status_label || log.status }}
              </span>
            </td>
            <td class="px-5 py-4">
              <p class="font-medium text-slate-900">{{ log.subject || '—' }}</p>
              <p class="line-clamp-1 text-xs text-slate-500">{{ log.body_preview }}</p>
            </td>
            <td class="px-5 py-4 text-slate-500">{{ formatDate(log.sent_at || log.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const filters = reactive({ status: '', channel: '' });

const statCards = computed(() => [
  { label: 'Total', value: store.logStats?.total ?? 0 },
  { label: 'Sent', value: store.logStats?.sent ?? 0 },
  { label: 'Failed', value: store.logStats?.failed ?? 0 },
  { label: 'Skipped', value: store.logStats?.skipped ?? 0 },
]);

onMounted(reload);

async function reload() {
  await store.fetchDeliveryLogs({
    status: filters.status || undefined,
    channel: filters.channel || undefined,
    per_page: 50,
  });
}

function statusClass(status) {
  if (status === 'sent') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'queued') return 'bg-amber-50 text-amber-700';
  return 'bg-zinc-100 text-slate-600';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
