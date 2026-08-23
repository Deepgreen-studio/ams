<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="reset-email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
      <input
        id="reset-email"
        v-model="form.email"
        type="email"
        autocomplete="username"
        required
        :disabled="loading"
        class="h-11 w-full rounded-xl bg-white px-3.5 text-sm text-zinc-900 outline-none ring-1 ring-zinc-200 transition placeholder:text-zinc-400 focus:ring-brand-500 disabled:bg-zinc-50"
      />
    </div>

    <div>
      <label for="reset-password" class="mb-1.5 block text-sm font-medium text-zinc-700">New password</label>
      <PasswordInput
        id="reset-password"
        v-model="form.password"
        autocomplete="new-password"
        placeholder=""
        required
        :disabled="loading"
      />
    </div>

    <div>
      <label for="reset-password-confirmation" class="mb-1.5 block text-sm font-medium text-zinc-700">Confirm password</label>
      <PasswordInput
        id="reset-password-confirmation"
        v-model="form.password_confirmation"
        autocomplete="new-password"
        placeholder=""
        required
        :disabled="loading"
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
      {{ loading ? 'Updating...' : 'Reset password' }}
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import { authService } from '@/modules/authentication/services/authService';

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
