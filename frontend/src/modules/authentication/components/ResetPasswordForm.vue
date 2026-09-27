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
      <label for="reset-password" class="mb-1.5 block text-sm font-medium text-zinc-700">New Password</label>
      <PasswordInput
        id="reset-password"
        v-model="form.password"
        autocomplete="new-password"
        placeholder=""
        required
        :disabled="loading"
        :tone="passwordTone"
      />
      <PasswordRequirements :password="form.password" />
      <p v-for="message in extraPasswordErrors" :key="message" class="mt-1.5 text-xs text-rose-600">
        {{ message }}
      </p>
    </div>

    <div>
      <label for="reset-password-confirmation" class="mb-1.5 block text-sm font-medium text-zinc-700">Confirm Password</label>
      <PasswordInput
        id="reset-password-confirmation"
        v-model="form.password_confirmation"
        autocomplete="new-password"
        placeholder=""
        required
        :disabled="loading"
        :tone="confirmationTone"
      />
      <p v-if="confirmationError" class="mt-1.5 text-xs text-rose-600">{{ confirmationError }}</p>
      <p v-else-if="passwordsMatch" class="mt-1.5 text-xs text-emerald-600">Passwords match.</p>
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
      {{ loading ? 'Saving...' : submitLabel }}
    </button>
  </form>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import PasswordRequirements from '@/modules/authentication/components/PasswordRequirements.vue';
import { passwordIsValid } from '@/modules/authentication/utils/passwordRules';
import { authService } from '@/modules/authentication/services/authService';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const submitted = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const fieldErrors = ref({});

const isSetup = computed(() => route.query.setup === '1');
const submitLabel = computed(() => (isSetup.value ? 'Set password and continue' : 'Reset password'));

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: '',
  token: typeof route.query.token === 'string' ? route.query.token : '',
});

const passwordValid = computed(() => passwordIsValid(form.password));
const showRuleErrors = computed(() => submitted.value || form.password.length > 0);
const passwordsMatch = computed(
  () => form.password.length > 0 && form.password === form.password_confirmation,
);
const confirmationError = computed(() => {
  if (fieldErrors.value.password_confirmation?.[0]) {
    return fieldErrors.value.password_confirmation[0];
  }

  if ((submitted.value || form.password_confirmation.length > 0) && !passwordsMatch.value) {
    return 'Passwords do not match.';
  }

  return '';
});
const extraPasswordErrors = computed(() =>
  (fieldErrors.value.password || []).filter(
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
  fieldErrors.value = {};

  if (!passwordValid.value || form.password !== form.password_confirmation) {
    return;
  }

  loading.value = true;

  try {
    const { data } = await authService.resetPassword({ ...form });
    successMessage.value = data.message || 'Password updated successfully.';
    setTimeout(() => router.push({ name: 'login' }), 1200);
  } catch (err) {
    fieldErrors.value = err.errors || {};
    const hasFieldErrors = Object.keys(fieldErrors.value).length > 0;
    errorMessage.value = hasFieldErrors ? '' : err.message || 'Unable to reset password';
  } finally {
    loading.value = false;
  }
}
</script>
