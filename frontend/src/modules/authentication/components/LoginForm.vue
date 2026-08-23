<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="login-email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
      <input
        id="login-email"
        v-model="form.email"
        type="email"
        name="email"
        autocomplete="username"
        required
        :disabled="loading"
        class="h-11 w-full rounded-xl bg-white px-3.5 text-sm text-zinc-900 outline-none ring-1 ring-zinc-200 transition placeholder:text-zinc-400 focus:ring-brand-500 disabled:bg-zinc-50"
      />
    </div>

    <div>
      <label for="login-password" class="mb-1.5 block text-sm font-medium text-zinc-700">Password</label>
      <PasswordInput
        id="login-password"
        v-model="form.password"
        autocomplete="current-password"
        placeholder=""
        required
        :disabled="loading"
      />
    </div>

    <div class="flex items-center justify-between gap-3 pt-0.5">
      <RememberMeCheckbox v-model="form.remember" :disabled="loading" />
      <RouterLink :to="{ name: 'forgot-password' }" class="text-sm font-medium text-brand-600 hover:text-brand-700">
        Forgot password?
      </RouterLink>
    </div>

    <div
      v-if="sessionExpired && !errorMessage"
      class="rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800"
      role="status"
    >
      Your session has expired. Please sign in again.
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl bg-red-50 px-3.5 py-2.5 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <button
      type="submit"
      class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
      :disabled="loading"
    >
      <svg
        v-if="loading"
        class="h-4 w-4 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      {{ loading ? 'Signing in...' : 'Sign in' }}
    </button>
  </form>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import RememberMeCheckbox from '@/modules/authentication/components/RememberMeCheckbox.vue';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const errorMessage = ref('');
const loading = ref(false);
const sessionExpired = computed(
  () => route.query.reason === 'session' || authStore.sessionExpired
);

const form = reactive({
  email: '',
  password: '',
  remember: true,
});

async function onSubmit() {
  errorMessage.value = '';
  loading.value = true;

  try {
    await authStore.login({ ...form });
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
    if (redirect) {
      await router.replace(redirect);
    } else if (authStore.isPortalCustomer) {
      await router.replace({ name: 'portal.tickets.index' });
    } else {
      await router.replace({ name: 'dashboard' });
    }
  } catch (err) {
    errorMessage.value = err.message || 'Invalid credentials.';
  } finally {
    loading.value = false;
  }
}
</script>
