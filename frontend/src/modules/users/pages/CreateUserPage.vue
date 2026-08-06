<template>
  <div>
    <PageHeader title="Create user" description="Provision a new platform user account." />

    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <UserForm
        :loading="usersStore.saving"
        :errors="usersStore.fieldErrors"
        :error="usersStore.error || ''"
        submit-label="Create user"
        require-password
        @submit="onSubmit"
        @cancel="router.push({ name: 'users.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import UserForm from '@/modules/users/components/UserForm.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const router = useRouter();
const usersStore = useUsersStore();

async function onSubmit(payload) {
  const user = await usersStore.createUser(payload);
  await router.push({ name: 'users.show', params: { id: user.uuid } });
}
</script>
