<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="store.loading"
        @click="reload"
      >
        {{ store.loading ? 'Refreshing…' : 'Refresh' }}
      </button>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
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
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
          <p class="text-sm text-slate-500">
            Delivery history across email and in-app channels.
          </p>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.status"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.channel"
              wrapper-class="min-w-[9.5rem]"
              :options="channelOptions"
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

      <div v-if="store.loading && !store.logs.length" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.logs.length"
        title="No delivery logs found"
        description="Try adjusting your filters or wait for new notifications to be sent."
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
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="reload"
          >
            Refresh
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Channel</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Recipient</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subject</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in store.logs"
              :key="log.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4 font-medium text-slate-900">
                {{ log.event_label || log.event_key }}
              </td>
              <td class="px-5 py-4">
                <span class="inline-flex rounded-[8px] bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                  {{ log.channel_label || log.channel }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">
                {{ log.recipient || log.notifiable?.email || '—' }}
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="statusClass(log.status)"
                >
                  {{ log.status_label || log.status }}
                </span>
              </td>
              <td class="max-w-xs px-5 py-4">
                <p class="font-medium text-slate-900">{{ log.subject || '—' }}</p>
                <p class="mt-0.5 line-clamp-1 text-xs text-slate-500">{{ log.body_preview }}</p>
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                {{ formatDate(log.sent_at || log.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="store.logMeta?.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination
          :meta="store.logMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import {
  ArrowPathIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  InboxStackIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const filters = reactive({
  status: '',
  channel: '',
  page: 1,
  per_page: 25,
});

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'sent', label: 'Sent' },
  { value: 'queued', label: 'Queued' },
  { value: 'failed', label: 'Failed' },
  { value: 'skipped', label: 'Skipped' },
];

const channelOptions = [
  { value: '', label: 'All channels' },
  { value: 'email', label: 'Email' },
  { value: 'in_app', label: 'In-App' },
];

const statCards = computed(() => [
  {
    label: 'Total',
    value: store.logStats?.total ?? 0,
    icon: InboxStackIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Sent',
    value: store.logStats?.sent ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Failed',
    value: store.logStats?.failed ?? 0,
    icon: ExclamationCircleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-500',
  },
  {
    label: 'Skipped',
    value: store.logStats?.skipped ?? 0,
    icon: ArrowPathIcon,
    iconBg: 'bg-zinc-100',
    iconColor: 'text-slate-500',
  },
]);

onMounted(reload);

async function reload() {
  await store.fetchDeliveryLogs({
    status: filters.status || undefined,
    channel: filters.channel || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  reload();
}

function resetFilters() {
  filters.status = '';
  filters.channel = '';
  filters.page = 1;
  reload();
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  reload();
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
