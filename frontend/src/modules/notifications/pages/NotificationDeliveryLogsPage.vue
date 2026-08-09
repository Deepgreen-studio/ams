<template>
  <div>
    <!-- <PageHeader title="Delivery Logs" description="Outbound notification delivery history" /> -->
    <NotificationsSubnav />

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
      <div v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All statuses</option>
        <option value="sent">Sent</option>
        <option value="queued">Queued</option>
        <option value="failed">Failed</option>
        <option value="skipped">Skipped</option>
      </select>
      <select v-model="filters.channel" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All channels</option>
        <option value="email">Email</option>
        <option value="in_app">In-App</option>
      </select>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @click="reload">Refresh</button>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Event</th>
            <th class="px-4 py-3">Channel</th>
            <th class="px-4 py-3">Recipient</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Subject</th>
            <th class="px-4 py-3">When</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="log in store.logs" :key="log.uuid">
            <td class="px-4 py-3">{{ log.event_label || log.event_key }}</td>
            <td class="px-4 py-3">{{ log.channel_label || log.channel }}</td>
            <td class="px-4 py-3">{{ log.recipient || log.notifiable?.email || '—' }}</td>
            <td class="px-4 py-3">{{ log.status_label || log.status }}</td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ log.subject || '—' }}</p>
              <p class="line-clamp-1 text-xs text-slate-500">{{ log.body_preview }}</p>
            </td>
            <td class="px-4 py-3 text-slate-500">{{ formatDate(log.sent_at || log.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
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

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
