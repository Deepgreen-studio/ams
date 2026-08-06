<template>
  <div class="space-y-4 text-center">
    <h2 class="text-lg font-semibold text-slate-900">Email verification</h2>
    <p class="text-sm text-slate-500">{{ statusMessage }}</p>
    <ErrorState v-if="errorMessage" title="Verification failed" :message="errorMessage" />
    <RouterLink
      v-if="done"
      :to="{ name: 'login' }"
      class="inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
    >
      Continue to login
    </RouterLink>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ErrorState from '@/components/ui/ErrorState.vue';
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
