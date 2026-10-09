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
      <FieldError :message="displayErrors.email?.[0] || ''" />
    </div>

    <div>
      <label for="password" class="mb-1 block text-sm font-medium text-slate-700">New Password</label>
      <PasswordInput
        id="password"
        v-model="form.password"
        autocomplete="new-password"
        required
        :disabled="loading"
        :tone="passwordTone"
      />
      <PasswordRequirements :password="form.password" />
      <FieldError :message="extraPasswordErrors.join(' ')" />
    </div>

    <div>
      <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm Password</label>
      <PasswordInput
        id="password_confirmation"
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
    <div
      v-if="errorMessage"
      class="rounded-xl bg-red-50 px-3.5 py-2.5 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

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
import FieldError from '@/components/ui/FieldError.vue';
import { computed, reactive, ref } from 'vue';
import { useFieldErrors } from '@/composables/useFieldErrors';
import { useRoute, useRouter } from 'vue-router';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import PasswordRequirements from '@/modules/authentication/components/PasswordRequirements.vue';
import { passwordIsValid } from '@/modules/authentication/utils/passwordRules';
import { authService } from '@/services/authService';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const submitted = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const apiErrors = ref({});

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: '',
  token: typeof route.query.token === 'string' ? route.query.token : '',
});

const { displayErrors } = useFieldErrors(form, () => apiErrors.value);

const passwordValid = computed(() => passwordIsValid(form.password));
const showRuleErrors = computed(() => submitted.value || form.password.length > 0);
const passwordsMatch = computed(
  () => form.password.length > 0 && form.password === form.password_confirmation,
);
const confirmationError = computed(() => {
  if (displayErrors.value.password_confirmation?.[0]) {
    return displayErrors.value.password_confirmation[0];
  }

  if ((submitted.value || form.password_confirmation.length > 0) && !passwordsMatch.value) {
    return 'Passwords do not match.';
  }

  return '';
});
const extraPasswordErrors = computed(() =>
  (displayErrors.value.password || []).filter(
    (message) => !/uppercase|lowercase|letter|number|symbol|8 characters/i.test(message),
  ),
);
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
  apiErrors.value = {};

  if (!passwordValid.value || form.password !== form.password_confirmation) {
    return;
  }

  loading.value = true;

  try {
    const { data } = await authService.resetPassword({ ...form });
    successMessage.value = data.message || 'Password updated successfully.';
    setTimeout(() => router.push({ name: 'login' }), 1200);
  } catch (err) {
    apiErrors.value = err.errors || {};
    const hasFieldErrors = Object.keys(apiErrors.value).length > 0;
    errorMessage.value = hasFieldErrors ? '' : err.message || 'Unable to reset password';
  } finally {
    loading.value = false;
  }
}
</script>
