<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="current-password" class="mb-1 block text-sm font-medium text-slate-700">Current password</label>
      <PasswordInput id="current-password" v-model="form.current_password" required :disabled="loading" />
    </div>

    <div>
      <label for="new-password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
      <PasswordInput id="new-password" v-model="form.password" autocomplete="new-password" required :disabled="loading" />
    </div>

    <div>
      <label for="new-password-confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm new password</label>
      <PasswordInput
        id="new-password-confirmation"
        v-model="form.password_confirmation"
        autocomplete="new-password"
        required
        :disabled="loading"
      />
    </div>

    <p v-if="successMessage" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
      {{ successMessage }}
    </p>
    <ErrorState v-if="errorMessage" title="Unable to change password" :message="errorMessage" />

    <button
      type="submit"
      class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
      :disabled="loading"
    >
      {{ loading ? 'Saving...' : 'Change password' }}
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import ErrorState from '@/components/ui/ErrorState.vue';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

async function onSubmit() {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const data = await authStore.changePassword({ ...form });
    successMessage.value = data.message || 'Password changed successfully. Please sign in again.';
    setTimeout(() => router.push({ name: 'login' }), 1000);
  } catch (err) {
    errorMessage.value = err.message || 'Unable to change password';
  } finally {
    loading.value = false;
  }
}
</script>
