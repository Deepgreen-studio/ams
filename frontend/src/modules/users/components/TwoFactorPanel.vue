<template>
  <section class="rounded-[12px] bg-white p-6 sm:p-8">
    <h3 class="text-base font-semibold text-slate-900">Multi-factor authentication</h3>
    <p class="mt-1 text-sm text-slate-500">
      Use an authenticator app. Super Admin accounts are asked to enroll after sign-in.
    </p>
    <p
      v-if="required"
      class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800"
    >
      Multi-factor authentication is required for this Super Admin account.
    </p>

    <div v-if="recoveryCodes.length" class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
      <p class="font-medium text-slate-900">Save these recovery codes. They are shown once.</p>
      <ul class="mt-2 grid gap-1 font-mono text-xs sm:grid-cols-2">
        <li v-for="code in recoveryCodes" :key="code">{{ code }}</li>
      </ul>
    </div>

    <div v-if="setup.secret" class="mt-4 space-y-3">
      <p class="text-sm text-slate-600">
        Add this key to your authenticator app, then enter the 6-digit code.
      </p>
      <p class="break-all rounded-xl bg-slate-50 px-3 py-2 font-mono text-sm text-slate-900">
        {{ setup.secret }}
      </p>
      <input
        v-model="code"
        type="text"
        inputmode="numeric"
        maxlength="6"
        class="h-11 w-full max-w-xs rounded-xl border border-slate-200 px-3 text-sm"
        placeholder="6-digit code"
      />
      <button
        type="button"
        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="saving || code.length !== 6"
        @click="confirm"
      >
        Confirm
      </button>
    </div>

    <div v-else-if="enabled" class="mt-4 space-y-3">
      <p class="text-sm font-medium text-emerald-700">Enabled</p>
      <template v-if="!isSuperAdmin">
        <input
          v-model="password"
          type="password"
          autocomplete="current-password"
          class="h-11 w-full max-w-xs rounded-xl border border-slate-200 px-3 text-sm"
          placeholder="Current password"
        />
        <button
          type="button"
          class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="saving || !password"
          @click="disable"
        >
          Disable
        </button>
      </template>
    </div>

    <button
      v-else
      type="button"
      class="mt-4 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
      :disabled="saving"
      @click="begin"
    >
      Enable MFA
    </button>

    <p v-if="error" class="mt-3 text-sm text-rose-600">{{ error }}</p>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { userService } from '@/modules/users/services/userService';

defineProps({
  enabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  isSuperAdmin: { type: Boolean, default: false },
});

const emit = defineEmits(['updated']);

const saving = ref(false);
const error = ref('');
const code = ref('');
const password = ref('');
const recoveryCodes = ref([]);
const setup = reactive({ secret: '', otpauth_url: '' });

async function begin() {
  error.value = '';
  saving.value = true;
  try {
    const { data } = await userService.beginTwoFactor();
    setup.secret = data.data?.secret || '';
    setup.otpauth_url = data.data?.otpauth_url || '';
  } catch (err) {
    error.value = err.message || 'Unable to start MFA setup.';
  } finally {
    saving.value = false;
  }
}

async function confirm() {
  error.value = '';
  saving.value = true;
  try {
    const { data } = await userService.confirmTwoFactor({ code: code.value });
    recoveryCodes.value = data.data?.recovery_codes || [];
    setup.secret = '';
    code.value = '';
    emit('updated', data.data?.user);
  } catch (err) {
    error.value = err.message || 'The authentication code is invalid.';
  } finally {
    saving.value = false;
  }
}

async function disable() {
  error.value = '';
  saving.value = true;
  try {
    const { data } = await userService.disableTwoFactor({ password: password.value });
    password.value = '';
    recoveryCodes.value = [];
    emit('updated', data.data?.user);
  } catch (err) {
    error.value = err.message || 'Unable to disable MFA.';
  } finally {
    saving.value = false;
  }
}
</script>
