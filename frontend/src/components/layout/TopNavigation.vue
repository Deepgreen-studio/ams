<template>
  <header class="sticky top-0 z-20 border-b border-zinc-200/80 bg-canvas/95 backdrop-blur">
    <div class="flex h-[90px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
      <!-- Left: page title -->
      <div class="flex min-w-0 items-center gap-3">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-full border border-zinc-200 bg-white p-2 text-zinc-600 hover:bg-zinc-50 lg:hidden"
          @click="appStore.toggleSidebar"
        >
          <Bars3Icon class="h-5 w-5" />
        </button>
        <h1 class="truncate text-[1.75rem] font-bold tracking-tight text-zinc-900">
          {{ pageTitle }}
        </h1>
      </div>

      <!-- Right: search + notifications + profile pill -->
      <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <label class="relative hidden w-[min(100%,22rem)] shrink md:block lg:w-[26rem]">
          <span class="sr-only">Search</span>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search for anything..."
            class="w-full rounded-full border-0 bg-white py-3 pl-5 pr-4 text-sm text-zinc-800 ring-1 ring-zinc-100 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500/25"
          />
        </label>

        <div class="relative shrink-0">
          <button
            type="button"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-zinc-600 ring-1 ring-zinc-100 hover:bg-zinc-50"
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
            class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-zinc-100 bg-white"
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

        <div class="relative shrink-0">
          <button
            type="button"
            class="flex items-center gap-3 rounded-full bg-white py-1.5 pl-1.5 pr-3 ring-1 ring-zinc-100 transition hover:bg-zinc-50"
            :aria-expanded="profileOpen"
            aria-haspopup="menu"
            @click="toggleProfile"
          >
            <UserAvatar
              :src="avatarUrl"
              :name="displayName"
              :first-name="authStore.user?.first_name || ''"
              :last-name="authStore.user?.last_name || ''"
              size="md"
            />
            <span class="hidden min-w-0 text-left sm:block">
              <span class="block max-w-[9rem] truncate text-sm font-semibold text-zinc-900 lg:max-w-[12rem]">
                {{ displayName }}
              </span>
              <span class="block max-w-[9rem] truncate text-xs text-zinc-500 lg:max-w-[12rem]">
                {{ roleLabel }}
              </span>
            </span>
            <ChevronDownIcon
              class="hidden h-4 w-4 shrink-0 text-zinc-400 transition sm:block"
              :class="profileOpen ? 'rotate-180' : ''"
            />
          </button>

          <div
            v-if="profileOpen"
            class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-2xl border border-zinc-100 bg-white py-1"
            role="menu"
          >
            <RouterLink
              :to="{ name: 'profile' }"
              class="block px-4 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50"
              role="menuitem"
              @click="profileOpen = false"
            >
              My profile
            </RouterLink>
            <RouterLink
              :to="{ name: 'change-password' }"
              class="block px-4 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50"
              role="menuitem"
              @click="profileOpen = false"
            >
              Change password
            </RouterLink>
            <button
              type="button"
              class="block w-full px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50"
              role="menuitem"
              :disabled="authStore.loading"
              @click="onLogout"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Bars3Icon, BellIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAppStore } from '@/stores/app';
import { useAuthStore } from '@/modules/authentication/stores/auth';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';
import UserAvatar from '@/components/ui/UserAvatar.vue';
import { getUserAvatarUrl } from '@/utils/avatar';

const appStore = useAppStore();
const authStore = useAuthStore();
const notificationsStore = useNotificationsStore();
const router = useRouter();
const route = useRoute();
const bellOpen = ref(false);
const profileOpen = ref(false);
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

const avatarUrl = computed(() => getUserAvatarUrl(authStore.user));

onMounted(() => {
  notificationsStore.fetchCenter().catch(() => {});
  pollTimer = window.setInterval(() => {
    notificationsStore.fetchUnreadCount();
  }, 60000);
  document.addEventListener('click', onDocumentClick);
});

onUnmounted(() => {
  if (pollTimer) window.clearInterval(pollTimer);
  document.removeEventListener('click', onDocumentClick);
});

function onDocumentClick(event) {
  const target = event.target;
  if (!(target instanceof Element)) return;
  if (!target.closest('header')) {
    bellOpen.value = false;
    profileOpen.value = false;
  }
}

async function toggleBell() {
  profileOpen.value = false;
  bellOpen.value = !bellOpen.value;
  if (bellOpen.value) {
    await notificationsStore.fetchCenter().catch(() => {});
  }
}

function toggleProfile() {
  bellOpen.value = false;
  profileOpen.value = !profileOpen.value;
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
  profileOpen.value = false;
  await authStore.logout();
  await router.push({ name: 'login' });
}
</script>
