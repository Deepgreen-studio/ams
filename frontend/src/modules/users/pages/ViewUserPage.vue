<template>
  <div>
    <PageHeader
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
    </PageHeader>

    <div
      v-if="usersStore.loading && !usersStore.currentUser"
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="usersStore.currentUser" class="grid gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-6">
        <ProfileCard :user="usersStore.currentUser" />

        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Personal information
          </h3>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs text-slate-500">Gender</dt>
              <dd class="text-sm text-slate-900">{{ usersStore.currentUser.gender || '—' }}</dd>
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
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import { formatDate } from '@/utils/formatters';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ProfileCard from '@/modules/users/components/ProfileCard.vue';
import { useUsersStore } from '@/modules/users/stores/users';

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
const showDelete = ref(false);

onMounted(() => {
  usersStore.fetchUser(route.params.id);
});

async function onDelete() {
  await usersStore.deleteUser(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'users.index' });
}
</script>
