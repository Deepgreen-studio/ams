<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="forgot-email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
      <input
        id="forgot-email"
        v-model="email"
        type="email"
        autocomplete="username"
        required
        :disabled="loading"
        class="h-11 w-full rounded-xl bg-white px-3.5 text-sm text-zinc-900 outline-none ring-1 ring-zinc-200 transition placeholder:text-zinc-400 focus:ring-brand-500 disabled:bg-zinc-50"
      />
    </div>

    <p v-if="successMessage" class="rounded-xl bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-800">
      {{ successMessage }}
    </p>
    <div
      v-if="errorMessage"
      class="rounded-xl bg-red-50 px-3.5 py-2.5 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <button
      type="submit"
      class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-600 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60"
      :disabled="loading"
    >
      {{ loading ? 'Sending...' : 'Send reset link' }}
    </button>

    <RouterLink :to="{ name: 'login' }" class="block text-center text-sm font-medium text-brand-600 hover:text-brand-700">
      Back to sign in
    </RouterLink>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { authService } from '@/modules/authentication/services/authService';

const email = ref('');
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

async function onSubmit() {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const { data } = await authService.forgotPassword({ email: email.value });
    successMessage.value = data.message || 'If the account exists, a reset link has been sent.';
  } catch (err) {
    errorMessage.value = err.message || 'Unable to process request';
  } finally {
    loading.value = false;
  }
}
</script>
