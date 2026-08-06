<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="text-center">
      <h2 class="text-lg font-semibold text-slate-900">Forgot password</h2>
      <p class="mt-1 text-sm text-slate-500">Enter your email to receive a reset link.</p>
    </div>

    <div>
      <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
      <input
        id="email"
        v-model="email"
        type="email"
        required
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <p v-if="successMessage" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
      {{ successMessage }}
    </p>
    <ErrorState v-if="errorMessage" title="Request failed" :message="errorMessage" />

    <button
      type="submit"
      class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
      :disabled="loading"
    >
      {{ loading ? 'Sending...' : 'Send reset link' }}
    </button>

    <RouterLink :to="{ name: 'login' }" class="block text-center text-sm font-medium text-brand-600">
      Back to login
    </RouterLink>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import ErrorState from '@/components/ui/ErrorState.vue';
import { authService } from '@/services/authService';

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
