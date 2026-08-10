<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
      <RoleForm
        v-if="rolesStore.currentRole"
        :initial="rolesStore.currentRole"
        :lock-name="rolesStore.currentRole.is_system"
        :loading="rolesStore.saving"
        :errors="rolesStore.fieldErrors"
        :error="rolesStore.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="router.push({ name: 'roles.show', params: { id: route.params.id } })"
      />
      <div v-else class="h-40 animate-pulse rounded-[12px] bg-slate-100" />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import RoleForm from '@/modules/roles/components/RoleForm.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const route = useRoute();
const router = useRouter();
const rolesStore = useRolesStore();

onMounted(() => rolesStore.fetchRole(route.params.id));

async function onSubmit(payload) {
  await rolesStore.updateRole(route.params.id, payload);
  await router.push({ name: 'roles.show', params: { id: route.params.id } });
}
</script>
