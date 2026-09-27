<template>
  <div>
    <div class="max-w-lg rounded-xl border border-slate-200 bg-white p-6">
      <p v-if="authStore.user?.is_protected" class="text-sm text-slate-600">
        This account password cannot be changed.
      </p>
      <ChangePasswordForm v-else />
    </div>
  </div>
</template>

<script setup>
// import PageHeader from '@/components/ui/PageHeader.vue';
import ChangePasswordForm from '@/modules/authentication/components/ChangePasswordForm.vue';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const authStore = useAuthStore();
</script>
