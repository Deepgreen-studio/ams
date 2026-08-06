<template>
  <div>
    <PageHeader
      :title="rolesStore.currentRole?.display_name || 'Role details'"
      description="Role overview and activity history."
    >
      <template #actions>
        <RouterLink
          v-if="rolesStore.currentRole"
          :to="{ name: 'roles.permissions', params: { id: rolesStore.currentRole.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Assign permissions
        </RouterLink>
        <RouterLink
          v-if="rolesStore.currentRole"
          :to="{ name: 'roles.edit', params: { id: rolesStore.currentRole.uuid } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Edit
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="rolesStore.currentRole" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-semibold text-slate-900">
              {{ rolesStore.currentRole.display_name }}
            </h2>
            <RoleBadge
              :name="rolesStore.currentRole.name"
              :display-name="rolesStore.currentRole.is_system ? 'System' : 'Custom'"
              :system="rolesStore.currentRole.is_system"
            />
          </div>
          <p class="mt-2 text-sm text-slate-600">
            {{ rolesStore.currentRole.description || 'No description provided.' }}
          </p>
          <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Machine name</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.name }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Guard</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.guard_name }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Permissions</dt>
              <dd class="text-sm text-slate-900">
                {{
                  rolesStore.currentRole.permissions_count ??
                  rolesStore.currentRole.permissions?.length ??
                  0
                }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Users</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.users_count ?? 0 }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Assigned permissions
          </h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="permission in rolesStore.currentRole.permissions || []"
              :key="permission.id || permission.name"
              class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700"
            >
              {{ permission.name }}
            </span>
            <p
              v-if="!(rolesStore.currentRole.permissions || []).length"
              class="text-sm text-slate-500"
            >
              No permissions assigned.
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
          Activity history
        </h3>
        <p class="mt-3 text-3xl font-semibold text-slate-900">
          {{ rolesStore.activityHistory?.total ?? 0 }}
        </p>
        <ul class="mt-4 space-y-2">
          <li
            v-for="item in rolesStore.activityHistory?.recent || []"
            :key="item.id"
            class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"
          >
            <p class="font-medium text-slate-800">{{ item.description }}</p>
            <p class="mt-0.5 text-slate-500">{{ item.created_at }}</p>
          </li>
          <li
            v-if="!(rolesStore.activityHistory?.recent || []).length"
            class="text-sm text-slate-500"
          >
            No activity yet.
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const route = useRoute();
const rolesStore = useRolesStore();

onMounted(() => rolesStore.fetchRole(route.params.id));
</script>
