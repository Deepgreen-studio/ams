<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div>
      <label for="current-password" class="mb-1 block text-sm font-medium text-slate-700">Current Password</label>
      <PasswordInput id="current-password" v-model="form.current_password" required :disabled="loading" />
    </div>

    <div>
      <label for="new-password" class="mb-1 block text-sm font-medium text-slate-700">New Password</label>
      <PasswordInput
        id="new-password"
        v-model="form.password"
        autocomplete="new-password"
        required
        :disabled="loading"
        :tone="passwordTone"
      />
      <PasswordRequirements :password="form.password" />
    </div>

    <div>
      <label for="new-password-confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm New Password</label>
      <PasswordInput
        id="new-password-confirmation"
        v-model="form.password_confirmation"
        autocomplete="new-password"
        required
        :disabled="loading"
        :tone="confirmationTone"
      />
      <FieldError :message="confirmationError" />
        <p v-if="passwordsMatch" class="mt-1.5 text-xs text-emerald-600">Passwords match.</p>
    </div>

    <p v-if="successMessage" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
      {{ successMessage }}
    </p>
    <p v-if="errorMessage" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
      {{ errorMessage }}
    </p>

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
import FieldError from '@/components/ui/FieldError.vue';
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import PasswordRequirements from '@/modules/authentication/components/PasswordRequirements.vue';
import { passwordIsValid } from '@/modules/authentication/utils/passwordRules';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const loading = ref(false);
const submitted = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const passwordValid = computed(() => passwordIsValid(form.password));
const showRuleErrors = computed(() => submitted.value || form.password.length > 0);
const passwordsMatch = computed(
  () => form.password.length > 0 && form.password === form.password_confirmation,
);
const confirmationError = computed(() => {
  if ((submitted.value || form.password_confirmation.length > 0) && !passwordsMatch.value) {
    return 'Passwords do not match.';
  }

  return '';
});
const passwordTone = computed(() => {
  if (passwordValid.value) {
    return 'valid';
  }

  if (showRuleErrors.value) {
    return 'invalid';
  }

  return 'default';
});
const confirmationTone = computed(() => {
  if (passwordsMatch.value) {
    return 'valid';
  }

  if (confirmationError.value) {
    return 'invalid';
  }

  return 'default';
});

async function onSubmit() {
  submitted.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  if (!passwordValid.value || form.password !== form.password_confirmation) {
    return;
  }

  loading.value = true;

  try {
    const data = await authStore.changePassword({ ...form });
    successMessage.value = data.message || 'Password changed successfully. Please sign in again.';
    setTimeout(() => router.push({ name: 'login' }), 1000);
  } catch (err) {
    const passwordErrors = err.errors?.password;
    errorMessage.value = passwordErrors?.[0] || err.message || 'Unable to change password';
  } finally {
    loading.value = false;
  }
}
</script>
