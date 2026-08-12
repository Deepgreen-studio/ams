<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="rolesStore.currentRole && canAny('roles.assign', 'roles.update')"
        :to="{ name: 'roles.permissions', params: { id: rolesStore.currentRole.uuid } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Assign permissions
      </RouterLink>
      <RouterLink
        v-if="rolesStore.currentRole && can('roles.update')"
        :to="{ name: 'roles.edit', params: { id: rolesStore.currentRole.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-500" />
        Edit
      </RouterLink>
      <button
        v-if="rolesStore.currentRole && !rolesStore.currentRole.is_system && can('roles.delete')"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
        @click="showDelete = true"
      >
        <TrashIcon class="h-4 w-4 text-white" />
        Delete
      </button>
    </Teleport>

    <div
      v-if="rolesStore.loading && !rolesStore.currentRole"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="rolesStore.currentRole" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <div
              class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-[12px] bg-brand-50 text-base font-semibold text-brand-700"
            >
              {{ roleInitials }}
            </div>
            <div class="min-w-0 flex-1">
              <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
                {{ rolesStore.currentRole.display_name }}
              </h2>
              <p class="mt-1 text-sm text-slate-500">
                {{ rolesStore.currentRole.description || 'No description provided.' }}
              </p>
            </div>
          </div>

          <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
              <p class="text-xs text-zinc-500">Permissions</p>
              <p class="mt-1 text-lg font-semibold text-slate-900">{{ permissionCount }}</p>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
              <p class="text-xs text-zinc-500">Users</p>
              <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ rolesStore.currentRole.users_count ?? 0 }}
              </p>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
              <p class="text-xs text-zinc-500">Updated</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">
                {{ formatDate(rolesStore.currentRole.updated_at) || '—' }}
              </p>
            </div>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-900">Permissions</h3>
            <RouterLink
              v-if="canAny('roles.assign', 'roles.update')"
              :to="{ name: 'roles.permissions', params: { id: rolesStore.currentRole.uuid } }"
              class="text-sm font-medium text-brand-600 hover:text-brand-700"
            >
              Manage
            </RouterLink>
          </div>

          <div v-if="assignedPermissions.length" class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div
              v-for="permission in visiblePermissions"
              :key="permission.id || permission.name"
              class="rounded-[10px] bg-zinc-50 px-3.5 py-2.5 text-sm font-medium text-slate-800"
            >
              {{ permission.display_name || permission.name }}
            </div>
            <button
              v-if="assignedPermissions.length > visiblePermissionLimit"
              type="button"
              class="rounded-[10px] bg-brand-50 px-3.5 py-2.5 text-left text-sm font-medium text-brand-700 hover:bg-brand-100"
              @click="showAllPermissions = !showAllPermissions"
            >
              {{
                showAllPermissions
                  ? 'Show less'
                  : `+${assignedPermissions.length - visiblePermissionLimit} more`
              }}
            </button>
          </div>
          <p v-else class="mt-5 text-sm text-slate-500">
            No permissions assigned yet.
          </p>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Details</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Type</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ rolesStore.currentRole.is_system ? 'System' : 'Custom' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(rolesStore.currentRole.created_at) || '—' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(rolesStore.currentRole.updated_at) || '—' }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Activity</h3>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ rolesStore.activityHistory?.total ?? 0 }}
          </p>
          <p class="text-sm text-zinc-500">Logged events</p>

          <ul class="mt-4 space-y-2">
            <li
              v-for="item in rolesStore.activityHistory?.recent || []"
              :key="item.id"
              class="rounded-[10px] bg-zinc-50 px-3.5 py-2.5"
            >
              <p class="text-sm font-medium text-slate-800">{{ item.description }}</p>
              <p class="mt-0.5 text-xs text-zinc-500">{{ formatDate(item.created_at) }}</p>
            </li>
            <li
              v-if="!(rolesStore.activityHistory?.recent || []).length"
              class="text-sm text-zinc-500"
            >
              No recent activity.
            </li>
          </ul>
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete role"
      :message="`Soft delete ${rolesStore.currentRole?.display_name || 'this role'}?`"
      confirm-label="Delete"
      :loading="rolesStore.saving"
      @cancel="showDelete = false"
      @confirm="onDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { formatDate } from '@/utils/formatters';
import DeleteConfirmation from '@/modules/roles/components/DeleteConfirmation.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const route = useRoute();
const router = useRouter();
const rolesStore = useRolesStore();
const { can, canAny } = usePermissions();
const toast = useToast();
const showDelete = ref(false);
const showAllPermissions = ref(false);
const visiblePermissionLimit = 12;

const assignedPermissions = computed(() => rolesStore.currentRole?.permissions || []);

const visiblePermissions = computed(() =>
  showAllPermissions.value
    ? assignedPermissions.value
    : assignedPermissions.value.slice(0, visiblePermissionLimit)
);

const permissionCount = computed(
  () =>
    rolesStore.currentRole?.permissions_count ??
    rolesStore.currentRole?.permissions?.length ??
    0
);

const roleInitials = computed(() => {
  const name = rolesStore.currentRole?.display_name || rolesStore.currentRole?.name || 'R';
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('');
});

watch(
  () => rolesStore.error,
  (message) => {
    if (message) {
      toast.error(message, 'Error');
    }
  }
);

onMounted(() => {
  rolesStore.fetchRole(route.params.id);
});

async function onDelete() {
  try {
    await rolesStore.deleteRole(route.params.id);
    showDelete.value = false;
    toast.success('Role deleted successfully.');
    await router.push({ name: 'roles.index' });
  } catch {
    showDelete.value = false;
  }
}
</script>
