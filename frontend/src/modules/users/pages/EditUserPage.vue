<template>
  <div>
    <!-- <PageHeader title="Edit user" description="Update account details and status." /> -->

    <div
      v-if="usersStore.loading && !usersStore.currentUser"
      class="rounded-[12px] bg-white p-6 sm:p-8"
    >
      <div class="h-40 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <div v-else class="rounded-[12px] bg-white p-6 sm:p-8">
      <UserForm
        :initial="usersStore.currentUser || {}"
        :loading="usersStore.saving"
        :errors="usersStore.fieldErrors"
        :error="usersStore.error || ''"
        :role-options="roleOptions"
        submit-label="Save changes"
        :require-password="false"
        @submit="onSubmit"
        @cancel="router.push({ name: 'users.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import UserForm from '@/modules/users/components/UserForm.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';
import { useUsersStore } from '@/modules/users/stores/users';

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
const rolesStore = useRolesStore();

const roleOptions = computed(() => rolesStore.roles || []);

onMounted(() => {
  usersStore.fetchUser(route.params.id);
  rolesStore.fetchRoles({ per_page: 100, sort_by: 'name', sort_dir: 'asc', page: 1 });
});

async function onSubmit(payload) {
  await usersStore.updateUser(route.params.id, payload);
  await router.push({ name: 'users.show', params: { id: route.params.id } });
}
</script>
