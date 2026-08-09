<template>
  <div>
    <!-- <PageHeader
      :title="usersStore.currentUser?.full_name || 'User details'"
      description="Account overview and activity summary."
    >
      <template #actions>
        <RouterLink
          v-if="usersStore.currentUser"
          :to="{ name: 'users.edit', params: { id: usersStore.currentUser.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
        <button
          v-if="usersStore.currentUser"
          type="button"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
          @click="showDelete = true"
        >
          Delete
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="usersStore.currentUser"
          :to="{ name: 'users.edit', params: { id: usersStore.currentUser.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
        <button
          v-if="usersStore.currentUser"
          type="button"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
          @click="showDelete = true"
        >
          Delete
        </button>
    </Teleport>

    <div
      v-if="usersStore.loading && !usersStore.currentUser"
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="usersStore.currentUser" class="grid gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-6">
        <ProfileCard :user="usersStore.currentUser" />

        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Roles & access
            </h3>
            <RouterLink
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

        <div class="rounded-xl border border-slate-200 bg-white p-6">
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
              <dt class="text-xs text-slate-500">Gender</dt>
              <dd class="text-sm text-slate-900">{{ formatGender(usersStore.currentUser.gender) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Date of birth</dt>
              <dd class="text-sm text-slate-900">
                {{ usersStore.currentUser.date_of_birth || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Email verified</dt>
              <dd class="text-sm text-slate-900">
                {{ usersStore.currentUser.email_verified ? 'Yes' : 'No' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Updated by</dt>
              <dd class="text-sm text-slate-900">
                {{ usersStore.currentUser.updated_by?.full_name || '—' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Activity summary
          </h3>
          <p class="mt-3 text-3xl font-semibold text-slate-900">
            {{ usersStore.activitySummary?.total ?? 0 }}
          </p>
          <p class="text-sm text-slate-500">Logged events</p>
          <p class="mt-4 text-xs text-slate-500">
            Last activity:
            {{ formatDate(usersStore.activitySummary?.last_activity_at) || 'None yet' }}
          </p>

          <ul class="mt-4 space-y-2">
            <li
              v-for="item in usersStore.activitySummary?.recent || []"
              :key="item.id"
              class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"
            >
              <p class="font-medium text-slate-800">{{ item.description }}</p>
              <p class="mt-0.5 text-slate-500">{{ formatDate(item.created_at) }}</p>
            </li>
            <li
              v-if="!(usersStore.activitySummary?.recent || []).length"
              class="text-sm text-slate-500"
            >
              No recent activity.
            </li>
          </ul>
        </div>

        <div
          class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
        >
          Login history is architecture-ready and will appear here when authentication session
          recording is enabled.
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete user"
      :message="`Soft delete ${usersStore.currentUser?.full_name || 'this user'}?`"
      confirm-label="Delete"
      :loading="usersStore.saving"
      @cancel="showDelete = false"
      @confirm="onDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { useToast } from '@/composables/useToast';
import { formatDate } from '@/utils/formatters';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ProfileCard from '@/modules/users/components/ProfileCard.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
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

function formatGender(value) {
  if (!value) {
    return '—';
  }

  return String(value)
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
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
