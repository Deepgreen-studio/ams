<template>
  <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden"
          @click="appStore.toggleSidebar"
        >
          <Bars3Icon class="h-5 w-5" />
        </button>
        <div>
          <p class="text-sm font-medium text-slate-900">Administration</p>
          <p class="text-xs text-slate-500">Application Management System</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative">
          <button
            type="button"
            class="relative rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50"
            @click="toggleBell"
          >
            <BellIcon class="h-5 w-5" />
            <span
              v-if="notificationsStore.unreadCount > 0"
              class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold text-white"
            >
              {{ notificationsStore.unreadCount > 99 ? '99+' : notificationsStore.unreadCount }}
            </span>
          </button>

          <div
            v-if="bellOpen"
            class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          >
            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
              <p class="text-sm font-semibold text-slate-900">Notifications</p>
              <button type="button" class="text-xs text-brand-700 hover:underline" @click="markAll">Mark all read</button>
            </div>
            <div class="max-h-80 overflow-y-auto">
              <button
                v-for="item in notificationsStore.recent"
                :key="item.id"
                type="button"
                class="block w-full border-b border-slate-50 px-3 py-2 text-left hover:bg-slate-50"
                :class="{ 'bg-brand-50/40': !item.is_read }"
                @click="openNotification(item)"
              >
                <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
                <p class="line-clamp-2 text-xs text-slate-500">{{ item.body }}</p>
              </button>
              <p v-if="notificationsStore.recent.length === 0" class="px-3 py-6 text-center text-xs text-slate-500">
                No notifications
              </p>
            </div>
            <RouterLink
              :to="{ name: 'notifications.center' }"
              class="block border-t border-slate-100 px-3 py-2 text-center text-xs font-medium text-brand-700 hover:bg-slate-50"
              @click="bellOpen = false"
            >
              Open notification center
            </RouterLink>
          </div>
        </div>

        <RouterLink
          :to="{ name: 'profile' }"
          class="hidden text-sm font-medium text-slate-600 hover:text-slate-900 sm:inline"
        >
          Profile
        </RouterLink>
        <RouterLink
          :to="{ name: 'change-password' }"
          class="hidden text-sm font-medium text-slate-600 hover:text-slate-900 sm:inline"
        >
          Change password
        </RouterLink>
        <span class="hidden text-sm text-slate-600 sm:inline">
          {{ authStore.user?.full_name || authStore.user?.name }}
        </span>
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
          :disabled="authStore.loading"
          @click="onLogout"
        >
          Logout
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Bars3Icon, BellIcon } from '@heroicons/vue/24/outline';
import { RouterLink, useRouter } from 'vue-router';
import { useAppStore } from '@/stores/app';
import { useAuthStore } from '@/modules/authentication/stores/auth';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const appStore = useAppStore();
const authStore = useAuthStore();
const notificationsStore = useNotificationsStore();
const router = useRouter();
const bellOpen = ref(false);
let pollTimer = null;

onMounted(() => {
  notificationsStore.fetchCenter().catch(() => {});
  pollTimer = window.setInterval(() => {
    notificationsStore.fetchUnreadCount();
  }, 60000);
});

onUnmounted(() => {
  if (pollTimer) window.clearInterval(pollTimer);
});

async function toggleBell() {
  bellOpen.value = !bellOpen.value;
  if (bellOpen.value) {
    await notificationsStore.fetchCenter().catch(() => {});
  }
}

async function markAll() {
  await notificationsStore.markAllRead();
  await notificationsStore.fetchCenter();
}

async function openNotification(item) {
  if (!item.is_read) {
    await notificationsStore.markRead(item.id);
  }
  bellOpen.value = false;
  if (item.data?.ticket_uuid) {
    await router.push({ name: 'support.tickets.show', params: { id: item.data.ticket_uuid } });
  } else {
    await router.push({ name: 'notifications.center' });
  }
}

async function onLogout() {
  await authStore.logout();
  await router.push({ name: 'login' });
}
</script>
