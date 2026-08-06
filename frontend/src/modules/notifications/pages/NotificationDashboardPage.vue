<template>
  <div>
    <PageHeader
      title="Notification Dashboard"
      description="Enterprise notification center overview across channels."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'notifications.center' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Open center
        </RouterLink>
        <RouterLink
          :to="{ name: 'notifications.unread' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Unread
        </RouterLink>
      </template>
    </PageHeader>

    <NotificationsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent notifications</h2>
          <RouterLink :to="{ name: 'notifications.history' }" class="text-xs font-medium text-brand-700 hover:underline">
            View history
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recent.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <div v-else-if="!store.recent.length" class="py-8 text-center text-sm text-slate-500">
          No notifications yet.
        </div>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in store.recent" :key="item.uuid" class="flex items-start justify-between gap-3 py-3">
            <div>
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.message || item.body }}</p>
            </div>
            <span
              class="mt-1 h-2 w-2 shrink-0 rounded-full"
              :class="item.is_read ? 'bg-slate-300' : 'bg-brand-600'"
            />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">Channels</h2>
        <ul class="divide-y divide-slate-100">
          <li
            v-for="channel in store.channelCatalog"
            :key="channel.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <p class="text-sm font-medium text-slate-900">{{ channel.name }}</p>
              <p class="text-xs text-slate-500">{{ channel.description }}</p>
            </div>
            <span
              class="rounded-full px-2.5 py-1 text-xs font-medium"
              :class="channel.is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
            >
              {{ channel.is_implemented ? (channel.is_enabled ? 'Enabled' : 'Disabled') : 'Future' }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();

const statCards = computed(() => [
  { label: 'Total', value: store.dashboardStats?.total ?? 0 },
  { label: 'Unread', value: store.unreadCount ?? store.dashboardStats?.unread ?? 0 },
  { label: 'Sent', value: store.dashboardStats?.sent ?? 0 },
  { label: 'Failed', value: store.dashboardStats?.failed ?? 0 },
]);

onMounted(() => store.fetchDashboard());
</script>
