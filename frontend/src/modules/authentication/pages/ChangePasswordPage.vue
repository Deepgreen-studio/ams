<template>
  <div>
    <PageHeader title="Change password" description="Update your account password securely." />
    <div class="max-w-lg rounded-xl border border-slate-200 bg-white p-6">
      <ChangePasswordForm />
    </div>
  </div>
</template>

<script setup>
import PageHeader from '@/components/ui/PageHeader.vue';
import ChangePasswordForm from '@/modules/authentication/components/ChangePasswordForm.vue';
</script>
