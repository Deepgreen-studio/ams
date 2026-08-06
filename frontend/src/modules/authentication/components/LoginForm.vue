<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="login-email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
      <input
        id="login-email"
        v-model="form.email"
        type="email"
        autocomplete="username"
        required
        :disabled="loading"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <div>
      <label for="login-password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
      <PasswordInput
        id="login-password"
        v-model="form.password"
        autocomplete="current-password"
        required
        :disabled="loading"
      />
    </div>

    <div class="flex items-center justify-between gap-3">
      <RememberMeCheckbox v-model="form.remember" :disabled="loading" />
      <RouterLink :to="{ name: 'forgot-password' }" class="text-sm font-medium text-brand-600 hover:text-brand-700">
        Forgot password?
      </RouterLink>
    </div>

    <ErrorState v-if="errorMessage" title="Login failed" :message="errorMessage" />

    <button
      type="submit"
      class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
      :disabled="loading"
    >
      {{ loading ? 'Signing in...' : 'Sign in' }}
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import ErrorState from '@/components/ui/ErrorState.vue';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import RememberMeCheckbox from '@/modules/authentication/components/RememberMeCheckbox.vue';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const errorMessage = ref('');
const loading = ref(false);

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
