<template>
  <div class="space-y-4 text-center">
    <p class="text-sm text-zinc-500">{{ statusMessage }}</p>
    <div
      v-if="errorMessage"
      class="rounded-xl bg-red-50 px-3.5 py-2.5 text-left text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>
    <RouterLink
      v-if="done"
      :to="{ name: 'login' }"
      class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700"
    >
      Continue to sign in
    </RouterLink>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { authService } from '@/modules/authentication/services/authService';

const route = useRoute();
const statusMessage = ref('Verifying your email address...');
const errorMessage = ref('');
const done = ref(false);

onMounted(async () => {
  const verifyUrl = typeof route.query.verify_url === 'string' ? route.query.verify_url : null;

  if (!verifyUrl) {
    errorMessage.value = 'Missing verification link.';
    statusMessage.value = 'Unable to verify email.';
    done.value = true;
    return;
  }

  try {
    const { data } = await authService.verifyEmail(verifyUrl);
    statusMessage.value = data.message || 'Email verified successfully.';
    done.value = true;
  } catch (err) {
    errorMessage.value = err.message || 'Invalid or expired verification link.';
    statusMessage.value = 'Verification failed.';
    done.value = true;
  }
});
</script>
