<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="text-center">
      <h2 class="text-lg font-semibold text-slate-900">Reset password</h2>
      <p class="mt-1 text-sm text-slate-500">Choose a new password for your account.</p>
    </div>

    <div>
      <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        required
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <div>
      <label for="password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        required
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <div>
      <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
      <input
        id="password_confirmation"
        v-model="form.password_confirmation"
        type="password"
        required
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
      />
    </div>

    <p v-if="successMessage" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
      {{ successMessage }}
    </p>
    <ErrorState v-if="errorMessage" title="Reset failed" :message="errorMessage" />

    <button
      type="submit"
      class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
      :disabled="loading"
    >
      {{ loading ? 'Updating...' : 'Reset password' }}
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ErrorState from '@/components/ui/ErrorState.vue';
import { authService } from '@/services/authService';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: '',
  token: typeof route.query.token === 'string' ? route.query.token : '',
});

async function onSubmit() {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const { data } = await authService.resetPassword({ ...form });
    successMessage.value = data.message || 'Password updated successfully.';
    setTimeout(() => router.push({ name: 'login' }), 1200);
  } catch (err) {
    errorMessage.value = err.message || 'Unable to reset password';
  } finally {
    loading.value = false;
  }
}
</script>
