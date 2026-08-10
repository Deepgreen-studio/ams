<template>
  <div class="space-y-6">
    <div class="grid gap-6 xl:grid-cols-12">
      <aside class="xl:col-span-4">
        <div class="overflow-hidden  bg-white p-6 rounded-[25px]">
          <AvatarUpload
            :user="usersStore.profile"
            :loading="usersStore.saving"
            :error="usersStore.fieldErrors.avatar?.[0] || ''"
            @upload="onUpload"
          />

          <div v-if="usersStore.profile" class="mt-6 border-t border-slate-100 pt-5">
            <ProfileCard
              :user="usersStore.profile"
              embedded
              hide-avatar
            />
          </div>
          <div v-else class="mt-6 space-y-3">
            <div class="mx-auto h-5 w-40 animate-pulse rounded bg-slate-100" />
            <div class="mx-auto h-4 w-48 animate-pulse rounded bg-slate-100" />
            <div class="mt-4 h-40 animate-pulse rounded-xl bg-slate-100" />
          </div>
        </div>
      </aside>

      <section class="xl:col-span-8">
        <div class="rounded-[25px] bg-white p-6 sm:p-8">

          <UserForm
            v-if="usersStore.profile"
            layout="profile"
            :initial="usersStore.profile"
            :loading="usersStore.saving"
            :errors="usersStore.fieldErrors"
            :error="usersStore.error || ''"
            submit-label="Update Profile"
            :show-password="false"
            :show-status="false"
            :show-role="false"
            @submit="onSubmit"
            @cancel="usersStore.fetchProfile()"
          />
          <div v-else class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
              <div v-for="n in 6" :key="n" class="h-12 animate-pulse rounded-xl bg-slate-100" />
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import AvatarUpload from '@/modules/users/components/AvatarUpload.vue';
import ProfileCard from '@/modules/users/components/ProfileCard.vue';
import UserForm from '@/modules/users/components/UserForm.vue';
import { useToast } from '@/composables/useToast';
import { useUsersStore } from '@/modules/users/stores/users';
import { useAuthStore } from '@/modules/authentication/stores/auth';

const usersStore = useUsersStore();
const authStore = useAuthStore();
const toast = useToast();

watch(
  () => usersStore.successMessage,
  (message) => {
    if (message) {
      toast.success(message);
    }
  }
);

watch(
  () => usersStore.error,
  (message) => {
    if (message) {
      toast.error(message, 'Error');
    }
  }
);

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
