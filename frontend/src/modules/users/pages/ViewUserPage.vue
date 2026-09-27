<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="usersStore.currentUser && can('users.update')"
          :to="{ name: 'users.edit', params: { id: usersStore.currentUser.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
        <button
          v-if="usersStore.currentUser && can('users.delete') && !usersStore.currentUser.is_protected"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Soft Delete
        </button>
    </Teleport>

    <div
      v-if="usersStore.loading && !usersStore.currentUser"
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="usersStore.currentUser" class="grid gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-6">
        <ProfileCard :user="usersStore.currentUser" />

        <div class="rounded-[12px] bg-white p-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Roles & access
            </h3>
            <RouterLink
              v-if="can('users.assign-roles')"
              :to="{ name: 'users.edit', params: { id: usersStore.currentUser.uuid } }"
              class="text-sm font-medium text-brand-700 hover:text-brand-800"
            >
              Change role
            </RouterLink>
          </div>

          <div v-if="assignedRoles.length" class="mt-4 flex flex-wrap gap-2">
            <RoleBadge
              v-for="role in assignedRoles"
              :key="role.uuid || role.name"
              :name="role.name"
              :display-name="role.display_name"
              :system="Boolean(role.is_system)"
            />
          </div>
          <p v-else class="mt-4 text-sm text-slate-500">
            No role assigned yet. Edit this user to assign one.
          </p>

          <dl class="mt-5 grid gap-4 sm:grid-cols-2 border-t border-slate-100 pt-5">
            <div>
              <dt class="text-xs text-slate-500">Role name</dt>
              <dd class="text-sm text-slate-900">
                {{ primaryRole?.display_name || primaryRole?.name || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Machine name</dt>
              <dd class="text-sm text-slate-900">{{ primaryRole?.name || '—' }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Personal information
          </h3>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs text-slate-500">First name</dt>
              <dd class="text-sm text-slate-900">{{ usersStore.currentUser.first_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Last name</dt>
              <dd class="text-sm text-slate-900">{{ usersStore.currentUser.last_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Email verified</dt>
              <dd class="text-sm text-slate-900">
                {{ usersStore.currentUser.email_verified ? 'Yes' : 'No' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Activity summary
            </h3>
            <RouterLink
              v-if="can('audit.view')"
              :to="{ name: 'audit.trail', query: { module: 'users' } }"
              class="text-sm font-medium text-brand-700 hover:text-brand-800"
            >
              View audit logs
            </RouterLink>
          </div>
          <p class="mt-3 text-3xl font-semibold text-slate-900">
            {{ usersStore.activitySummary?.total ?? 0 }}
          </p>
          <p class="text-sm text-slate-500">Logged lifecycle events</p>
          <p class="mt-4 text-xs text-slate-500">
            Last activity:
            {{ formatDateTime(usersStore.activitySummary?.last_activity_at) || 'None yet' }}
          </p>

          <ul class="mt-4 space-y-2">
            <li
              v-for="item in usersStore.activitySummary?.recent || []"
              :key="item.id"
              class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"
            >
              <p class="font-medium text-slate-800">{{ item.description }}</p>
              <p class="mt-0.5 text-slate-500">
                {{ item.causer?.full_name || 'System' }}
                ·
                {{ formatDateTime(item.created_at) || '—' }}
              </p>
            </li>
            <li
              v-if="!(usersStore.activitySummary?.recent || []).length"
              class="text-sm text-slate-500"
            >
              No recent activity.
            </li>
          </ul>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Login history
          </h3>
          <ul class="mt-4 space-y-2">
            <li
              v-for="entry in usersStore.loginHistory"
              :key="entry.uuid"
              class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"
            >
              <p class="font-medium text-slate-800">
                {{ entry.status || 'success' }}
                ·
                {{ entry.browser || entry.device || 'Unknown device' }}
              </p>
              <p class="mt-0.5 text-slate-500">
                {{ entry.ip_address || 'Unknown IP' }}
                ·
                {{ formatDateTime(entry.logged_in_at) || '—' }}
              </p>
            </li>
            <li v-if="!usersStore.loginHistory.length" class="text-sm text-slate-500">
              No login history yet.
            </li>
          </ul>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Sessions
          </h3>
          <ul class="mt-4 space-y-2">
            <li
              v-for="session in usersStore.sessions"
              :key="session.id"
              class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"
            >
              <div>
                <p class="font-medium text-slate-800">
                  {{ session.name || 'Session' }}
                  <span v-if="session.current"> · current</span>
                </p>
                <p class="mt-0.5 text-slate-500">
                  Last used {{ formatDateTime(session.last_used_at) || 'never' }}
                </p>
              </div>
              <button
                v-if="can('users.update')"
                type="button"
                class="text-sm font-medium text-rose-600 hover:text-rose-700"
                @click="onRevoke(session)"
              >
                Revoke
              </button>
            </li>
            <li v-if="!usersStore.sessions.length" class="text-sm text-slate-500">
              No active sessions.
            </li>
          </ul>
          <p class="mt-4 text-xs text-slate-500">
            MFA is {{ usersStore.currentUser.two_factor_enabled ? 'enabled' : 'not enabled' }}.
            Super Admin accounts are asked to enroll after sign-in.
          </p>
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Soft delete user"
      :message="`Soft delete ${usersStore.currentUser?.full_name || 'this user'}?`"
      confirm-label="Soft Delete"
      :loading="usersStore.saving"
      @cancel="showDelete = false"
      @confirm="onDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { formatDateTime } from '@/utils/formatters';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ProfileCard from '@/modules/users/components/ProfileCard.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
const { can } = usePermissions();
const toast = useToast();
const showDelete = ref(false);

const assignedRoles = computed(() => usersStore.currentUser?.roles || []);
const primaryRole = computed(() => assignedRoles.value[0] || null);

watch(
  () => usersStore.error,
  (message) => {
    if (message) {
      toast.error(message, 'Error');
    }
  }
);

onMounted(() => {
  usersStore.fetchUser(route.params.id);
});

async function onRevoke(session) {
  try {
    await usersStore.revokeSession(route.params.id, session.id);
    toast.success('Session revoked.');
  } catch {
    toast.error(usersStore.error || 'Unable to revoke session.');
  }
}

async function onDelete() {
  try {
    await usersStore.deleteUser(route.params.id);
    showDelete.value = false;
    toast.success('User deleted successfully.');
    await router.push({ name: 'users.index' });
  } catch {
    showDelete.value = false;
  }
}
</script>
