<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        autocomplete="username"
        required
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <div>
      <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        autocomplete="current-password"
        required
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <div class="flex items-center justify-between gap-3">
      <label class="inline-flex items-center gap-2 text-sm text-slate-600">
        <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Remember me
      </label>
      <RouterLink :to="{ name: 'forgot-password' }" class="text-sm font-medium text-brand-600 hover:text-brand-700">
        Forgot password?
      </RouterLink>
    </div>

    <ErrorState v-if="errorMessage" title="Login failed" :message="errorMessage" />

    <button
      type="submit"
      class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
      :disabled="authStore.loading"
    >
      {{ authStore.loading ? 'Signing in...' : 'Sign in' }}
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import ErrorState from '@/components/ui/ErrorState.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const errorMessage = ref('');

const form = reactive({
  email: '',
  password: '',
  remember: true,
});

async function onSubmit() {
  errorMessage.value = '';

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
      remember: form.remember,
    });

    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
    await router.replace(redirect);
  } catch (err) {
    errorMessage.value = err.message || 'Invalid credentials';
  }
}
</script>
