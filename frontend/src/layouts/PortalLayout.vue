<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
        <div>
          <p class="text-sm font-semibold text-slate-900">Customer Portal</p>
          <p class="text-xs text-slate-500">Support center</p>
        </div>
        <div class="flex items-center gap-3">
          <RouterLink
            :to="{ name: 'portal.tickets.index' }"
            class="text-sm font-medium text-slate-600 hover:text-slate-900"
          >
            My tickets
          </RouterLink>
          <RouterLink
            :to="{ name: 'portal.tickets.create' }"
            class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            New ticket
          </RouterLink>
          <span class="hidden text-sm text-slate-600 sm:inline">{{ authStore.user?.full_name }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

async function logout() {
  await authStore.logout();
  await router.push({ name: 'login' });
}
</script>
