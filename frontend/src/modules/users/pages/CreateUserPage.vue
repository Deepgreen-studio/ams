<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
      <UserForm
        :loading="usersStore.saving"
        :errors="usersStore.fieldErrors"
        :error="usersStore.error || ''"
        :show-role="canAssignRoles"
        :role-options="roleOptions"
        :company-options="companyOptions"
        submit-label="Create user"
        require-password
        @submit="onSubmit"
        @cancel="router.push({ name: 'users.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { usePermissions } from '@/composables/usePermissions';
import UserForm from '@/modules/users/components/UserForm.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';
import { companyService } from '@/modules/companies/services/companyService';
import { useUsersStore } from '@/modules/users/stores/users';

const router = useRouter();
const usersStore = useUsersStore();
const rolesStore = useRolesStore();
const { can } = usePermissions();

const canAssignRoles = computed(() => can('users.assign-roles'));
const roleOptions = computed(() => rolesStore.roles || []);
const companyOptions = ref([]);

onMounted(() => {
  loadCompanies();

  if (!canAssignRoles.value) {
    return;
  }

  rolesStore.fetchRoles({ per_page: 100, sort_by: 'name', sort_dir: 'asc', page: 1 });
});

async function loadCompanies() {
  try {
    const { data } = await companyService.list({
      per_page: 100,
      sort_by: 'company_name',
      sort_dir: 'asc',
      page: 1,
    });
    companyOptions.value = data.data?.companies?.items ?? [];
  } catch {
    companyOptions.value = [];
  }
}

async function onSubmit(payload) {
  const user = await usersStore.createUser(payload);
  await router.push({ name: 'users.show', params: { id: user.uuid } });
}
</script>
