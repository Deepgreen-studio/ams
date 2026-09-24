<template>
  <div class="flex min-h-screen items-center justify-center bg-canvas px-4 py-12">
    <div class="w-full max-w-[26rem]">
      <div class="mb-8 flex flex-col items-center text-center">
        <AmsMark class="h-11 w-11" />
        <p class="mt-4 text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-600">
          {{ appStore.appName }}
        </p>
        <h1 class="mt-2 text-[1.75rem] font-bold tracking-tight text-zinc-900">
          {{ copy.title }}
        </h1>
        <p v-if="copy.subtitle" class="mt-1.5 max-w-sm text-sm text-zinc-500">
          {{ copy.subtitle }}
        </p>
      </div>

      <div class="rounded-2xl bg-white p-7 ring-1 ring-zinc-100 sm:p-8">
        <RouterView />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import { useAppStore } from '@/stores/app';
import AmsMark from '@/components/brand/AmsMark.vue';

const appStore = useAppStore();
const route = useRoute();

const copy = computed(() => {
  switch (route.name) {
    case 'forgot-password':
      return {
        title: 'Forgot password',
        subtitle: 'Enter your email and we will send a reset link.',
      };
    case 'reset-password':
      return route.query.setup === '1'
        ? {
            title: 'Set your password',
            subtitle: 'Choose a password, then sign in with your email.',
          }
        : {
            title: 'Reset password',
            subtitle: 'Choose a new password for your account.',
          };
    case 'verify-email':
      return {
        title: 'Verify email',
        subtitle: '',
      };
    default:
      return {
        title: 'Sign in',
        subtitle: '',
      };
  }
});
</script>
