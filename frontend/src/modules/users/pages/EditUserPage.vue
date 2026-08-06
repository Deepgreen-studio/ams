<template>
  <div>
    <PageHeader title="Edit user" description="Update account details and status." />

    <div
      v-if="usersStore.loading && !usersStore.currentUser"
      class="rounded-xl border border-slate-200 bg-white p-6"
    >
      <div class="h-40 animate-pulse rounded bg-slate-100" />
    </div>

    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <UserForm
        :initial="usersStore.currentUser || {}"
        :loading="usersStore.saving"
        :errors="usersStore.fieldErrors"
        :error="usersStore.error || ''"
        submit-label="Save changes"
        :require-password="false"
        @submit="onSubmit"
        @cancel="router.push({ name: 'users.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import UserForm from '@/modules/users/components/UserForm.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();

onMounted(() => {
  usersStore.fetchUser(route.params.id);
});

async function onSubmit(payload) {
  await usersStore.updateUser(route.params.id, payload);
  await router.push({ name: 'users.show', params: { id: route.params.id } });
}
</script>
