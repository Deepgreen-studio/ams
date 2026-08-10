<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="rolesStore.currentRole"
        :to="{ name: 'roles.permissions', params: { id: rolesStore.currentRole.uuid } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Assign permissions
      </RouterLink>
      <RouterLink
        v-if="rolesStore.currentRole"
        :to="{ name: 'roles.edit', params: { id: rolesStore.currentRole.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-500" />
        Edit
      </RouterLink>
      <button
        v-if="rolesStore.currentRole && !rolesStore.currentRole.is_system"
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
        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div
              class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-[12px] bg-brand-50 text-base font-semibold text-brand-700"
            >
              {{ roleInitials }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
                  {{ rolesStore.currentRole.display_name }}
                </h2>
                <RoleBadge
                  :name="rolesStore.currentRole.name"
                  :display-name="rolesStore.currentRole.is_system ? 'System' : 'Custom'"
                  :system="rolesStore.currentRole.is_system"
                />
              </div>
              <p class="mt-1 text-sm text-slate-500">
                {{ rolesStore.currentRole.description || 'No description provided.' }}
              </p>
              <p class="mt-1 truncate text-xs text-zinc-400">{{ rolesStore.currentRole.uuid }}</p>
            </div>
          </div>

          <div class="mt-6">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
              Role details
            </p>
            <dl
              class="divide-y divide-slate-100 overflow-hidden rounded-[12px] border border-slate-100 bg-slate-50/60"
            >
              <div
                v-for="item in detailItems"
                :key="item.label"
                class="grid grid-cols-[7.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[8.5rem_1fr]"
              >
                <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                <dd class="truncate text-sm font-medium text-slate-900">{{ item.value }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Permissions & access
            </h3>
            <RouterLink
              :to="{ name: 'roles.permissions', params: { id: rolesStore.currentRole.uuid } }"
              class="text-sm font-medium text-brand-700 hover:text-brand-800"
            >
              Manage permissions
            </RouterLink>
          </div>

          <div v-if="assignedPermissions.length" class="mt-4 flex flex-wrap gap-2">
            <span
              v-for="permission in visiblePermissions"
              :key="permission.id || permission.name"
              class="rounded-[8px] bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700"
            >
              {{ permission.display_name || permission.name }}
            </span>
            <span
              v-if="assignedPermissions.length > visiblePermissionLimit"
              class="rounded-[8px] bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700"
            >
              +{{ assignedPermissions.length - visiblePermissionLimit }} more
            </span>
          </div>
          <p v-else class="mt-4 text-sm text-slate-500">
            No permissions assigned yet. Manage this role to assign permissions.
          </p>

          <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
            <div>
              <dt class="text-xs text-slate-500">Permission count</dt>
              <dd class="text-sm text-slate-900">{{ permissionCount }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Assigned users</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.users_count ?? 0 }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Role information
          </h3>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs text-slate-500">Display name</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.display_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Machine name</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Guard</dt>
              <dd class="text-sm text-slate-900">{{ rolesStore.currentRole.guard_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Type</dt>
              <dd class="text-sm text-slate-900">
                {{ rolesStore.currentRole.is_system ? 'System' : 'Custom' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Created</dt>
              <dd class="text-sm text-slate-900">
                {{ formatDate(rolesStore.currentRole.created_at) || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Updated</dt>
              <dd class="text-sm text-slate-900">
                {{ formatDate(rolesStore.currentRole.updated_at) || '—' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Activity summary
          </h3>
          <p class="mt-3 text-3xl font-semibold text-slate-900">
            {{ rolesStore.activityHistory?.total ?? 0 }}
          </p>
          <p class="text-sm text-slate-500">Logged events</p>
          <p class="mt-4 text-xs text-slate-500">
            Last activity:
            {{ formatDate(lastActivityAt) || 'None yet' }}
          </p>

          <ul class="mt-4 space-y-2">
            <li
              v-for="item in rolesStore.activityHistory?.recent || []"
              :key="item.id"
              class="rounded-[12px] bg-slate-50 px-3 py-2 text-xs text-slate-600"
            >
              <p class="font-medium text-slate-800">{{ item.description }}</p>
              <p class="mt-0.5 text-slate-500">{{ formatDate(item.created_at) }}</p>
            </li>
            <li
              v-if="!(rolesStore.activityHistory?.recent || []).length"
              class="text-sm text-slate-500"
            >
              No recent activity.
            </li>
          </ul>
        </div>

        <div
          class="rounded-[12px] border border-dashed border-zinc-300 bg-white p-6 text-sm text-slate-500"
        >
          User assignment history will appear here when role membership auditing is expanded.
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
import { useToast } from '@/composables/useToast';
import { formatDate } from '@/utils/formatters';
import DeleteConfirmation from '@/modules/roles/components/DeleteConfirmation.vue';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';

const route = useRoute();
const router = useRouter();
const rolesStore = useRolesStore();
const toast = useToast();
const showDelete = ref(false);
const visiblePermissionLimit = 24;

const assignedPermissions = computed(() => rolesStore.currentRole?.permissions || []);

const visiblePermissions = computed(() =>
  assignedPermissions.value.slice(0, visiblePermissionLimit),
);

const permissionCount = computed(
  () =>
    rolesStore.currentRole?.permissions_count ??
    rolesStore.currentRole?.permissions?.length ??
    0,
);

const lastActivityAt = computed(
  () => rolesStore.activityHistory?.recent?.[0]?.created_at || null,
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

const detailItems = computed(() => [
  { label: 'Machine name', value: rolesStore.currentRole?.name || '—' },
  { label: 'Guard', value: rolesStore.currentRole?.guard_name || '—' },
  { label: 'Permissions', value: permissionCount.value },
  { label: 'Users', value: rolesStore.currentRole?.users_count ?? 0 },
  { label: 'Created', value: formatDate(rolesStore.currentRole?.created_at) || '—' },
  { label: 'Updated', value: formatDate(rolesStore.currentRole?.updated_at) || '—' },
]);

watch(
  () => rolesStore.error,
  (message) => {
    if (message) {
      toast.error(message, 'Error');
    }
  },
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
