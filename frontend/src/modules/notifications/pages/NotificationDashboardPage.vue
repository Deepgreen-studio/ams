<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.center' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Open center
      </RouterLink>
      <RouterLink
        :to="{ name: 'notifications.unread' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Unread
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !store.recent.length" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Recent notifications</h2>
          <RouterLink
            :to="{ name: 'notifications.history' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View history
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recent.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <div v-else-if="!store.recent.length" class="py-10 text-center text-sm text-slate-500">
          No notifications yet.
        </div>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="item in store.recent"
            :key="item.uuid"
            class="flex items-start justify-between gap-3 py-3.5"
          >
            <div class="min-w-0">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ item.message || item.body }}</p>
            </div>
            <span
              class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
              :class="item.is_read ? 'bg-zinc-300' : 'bg-brand-600'"
            />
          </li>
        </ul>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Channels</h2>
        <ul class="divide-y divide-zinc-100">
          <li
            v-for="channel in store.channelCatalog"
            :key="channel.uuid"
            class="flex items-center justify-between gap-3 py-3.5"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900">{{ channel.name }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ channel.description }}</p>
            </div>
            <span
              class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
              :class="
                channel.is_implemented
                  ? channel.is_enabled
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-zinc-100 text-slate-500'
                  : 'bg-zinc-100 text-slate-500'
              "
            >
              {{ channel.is_implemented ? (channel.is_enabled ? 'Enabled' : 'Disabled') : 'Future' }}
            </span>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BellAlertIcon,
  CheckCircleIcon,
  EnvelopeIcon,
  ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();

const statCards = computed(() => [
  {
    label: 'Total',
    value: store.dashboardStats?.total ?? 0,
    icon: EnvelopeIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Unread',
    value: store.unreadCount ?? store.dashboardStats?.unread ?? 0,
    icon: BellAlertIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
  {
    label: 'Sent',
    value: store.dashboardStats?.sent ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Failed',
    value: store.dashboardStats?.failed ?? 0,
    icon: ExclamationCircleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-500',
  },
]);

onMounted(() => store.fetchDashboard());
</script>
