<template>
  <div>
    <PageHeader title="My profile" description="Update your personal information and avatar." />

    <div
      v-if="usersStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ usersStore.successMessage }}
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <AvatarUpload
            :user="usersStore.profile"
            :loading="usersStore.saving"
            :error="usersStore.fieldErrors.avatar?.[0] || ''"
            @upload="onUpload"
          />
        </div>
        <ProfileCard v-if="usersStore.profile" :user="usersStore.profile" />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <UserForm
          v-if="usersStore.profile"
          :initial="usersStore.profile"
          :loading="usersStore.saving"
          :errors="usersStore.fieldErrors"
          :error="usersStore.error || ''"
          submit-label="Update profile"
          :show-password="false"
          :show-status="false"
          @submit="onSubmit"
          @cancel="usersStore.fetchProfile()"
        />
        <div v-else class="h-40 animate-pulse rounded bg-slate-100" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AvatarUpload from '@/modules/users/components/AvatarUpload.vue';
import ProfileCard from '@/modules/users/components/ProfileCard.vue';
import UserForm from '@/modules/users/components/UserForm.vue';
import { useUsersStore } from '@/modules/users/stores/users';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const usersStore = useUsersStore();
const authStore = useAuthStore();

onMounted(() => {
  usersStore.fetchProfile();
});

async function onSubmit(payload) {
  const profile = await usersStore.updateProfile(payload);
  if (profile) {
    authStore.user = {
      ...authStore.user,
      ...profile,
      name: profile.full_name || profile.name,
    };
  }
}

async function onUpload(file) {
  const profile = await usersStore.uploadAvatar(file);
  if (profile) {
    authStore.user = {
      ...authStore.user,
      ...profile,
      name: profile.full_name || profile.name,
    };
  }
}
</script>
