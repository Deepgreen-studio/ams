<template>
  <div>
    <PageHeader title="Edit role" description="Update role details and metadata." />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
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
      <div v-else class="h-40 animate-pulse rounded bg-slate-100" />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
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
