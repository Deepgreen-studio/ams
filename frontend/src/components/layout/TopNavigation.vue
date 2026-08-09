<template>
  <header class="sticky top-0 z-20 bg-canvas/90 backdrop-blur">
    <div class="flex h-[4.5rem] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
      <div class="flex min-w-0 items-center gap-3">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-full border border-zinc-200 bg-white p-2 text-zinc-600 hover:bg-zinc-50 lg:hidden"
          @click="appStore.toggleSidebar"
        >
          <Bars3Icon class="h-5 w-5" />
        </button>
        <h1 class="truncate text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.75rem]">
          {{ pageTitle }}
        </h1>
      </div>

      <div class="hidden max-w-md flex-1 md:block lg:max-w-lg">
        <label class="relative block">
          <span class="sr-only">Search</span>
          <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" />
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search for anything..."
            class="w-full rounded-full border-0 bg-white py-2.5 pl-11 pr-4 text-sm text-zinc-800 shadow-sm ring-1 ring-zinc-100 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
          />
        </label>
      </div>

      <div class="flex items-center gap-3 sm:gap-4">
        <div class="relative">
          <button
            type="button"
            class="relative inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-zinc-600 shadow-sm ring-1 ring-zinc-100 hover:bg-zinc-50"
            @click="toggleBell"
          >
            <BellIcon class="h-5 w-5" />
            <span
              v-if="notificationsStore.unreadCount > 0"
              class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-semibold text-white"
            >
              {{ notificationsStore.unreadCount > 99 ? '99+' : notificationsStore.unreadCount }}
            </span>
          </button>

          <div
            v-if="bellOpen"
            class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-zinc-100 bg-white shadow-xl"
          >
            <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2.5">
              <p class="text-sm font-semibold text-zinc-900">Notifications</p>
              <button type="button" class="text-xs font-medium text-brand-600 hover:underline" @click="markAll">
                Mark all read
              </button>
            </div>
            <div class="max-h-80 overflow-y-auto">
              <button
                v-for="item in notificationsStore.recent"
                :key="item.id"
                type="button"
                class="block w-full border-b border-zinc-50 px-3 py-2.5 text-left hover:bg-zinc-50"
                :class="{ 'bg-brand-50/50': !item.is_read }"
                @click="openNotification(item)"
              >
                <p class="text-sm font-medium text-zinc-900">{{ item.title }}</p>
                <p class="line-clamp-2 text-xs text-zinc-500">{{ item.body }}</p>
              </button>
              <p v-if="notificationsStore.recent.length === 0" class="px-3 py-6 text-center text-xs text-zinc-500">
                No notifications
              </p>
            </div>
            <RouterLink
              :to="{ name: 'notifications.center' }"
              class="block border-t border-zinc-100 px-3 py-2.5 text-center text-xs font-medium text-brand-600 hover:bg-zinc-50"
              @click="bellOpen = false"
            >
              Open notification center
            </RouterLink>
          </div>
        </div>

        <RouterLink
          :to="{ name: 'profile' }"
          class="flex items-center gap-3 rounded-full py-1 pl-1 pr-2 transition hover:bg-white/70"
        >
          <span
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-zinc-200 text-sm font-semibold text-zinc-700 ring-2 ring-white"
          >
            <img
              v-if="avatarUrl"
              :src="avatarUrl"
              :alt="displayName"
              class="h-full w-full object-cover"
            />
            <span v-else>{{ initials }}</span>
          </span>
          <span class="hidden min-w-0 sm:block">
            <span class="block truncate text-sm font-semibold text-zinc-900">{{ displayName }}</span>
            <span class="block truncate text-xs text-zinc-500">{{ roleLabel }}</span>
          </span>
        </RouterLink>

        <button
          type="button"
          class="hidden rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 sm:inline-flex"
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
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Bars3Icon, BellIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAppStore } from '@/stores/app';
import { useAuthStore } from '@/modules/authentication/stores/auth';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const appStore = useAppStore();
const authStore = useAuthStore();
const notificationsStore = useNotificationsStore();
const router = useRouter();
const route = useRoute();
const bellOpen = ref(false);
const searchQuery = ref('');
let pollTimer = null;

const pageTitle = computed(() => {
  if (typeof route.meta?.title === 'string' && route.meta.title) {
    return route.meta.title;
  }
  if (route.name === 'dashboard') {
    return 'Dashboard';
  }
  const raw = typeof route.name === 'string' ? route.name.split('.').pop() : 'AMS';
  return String(raw)
    .replace(/[-_]/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
});

const displayName = computed(
  () => authStore.user?.full_name || authStore.user?.name || 'User',
);

const roleLabel = computed(() => {
  const roles = authStore.user?.roles;
  if (Array.isArray(roles) && roles.length > 0) {
    const first = typeof roles[0] === 'string' ? roles[0] : roles[0]?.name;
    if (first) {
      return String(first)
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
    }
  }
  return 'Administrator';
});

const avatarUrl = computed(() => authStore.user?.avatar_url || authStore.user?.avatar || null);

const initials = computed(() => {
  const name = displayName.value.trim();
  const parts = name.split(/\s+/).filter(Boolean);
  if (parts.length === 0) return 'U';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
});

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
