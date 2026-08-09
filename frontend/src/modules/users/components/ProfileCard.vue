<template>
  <div class="rounded-xl border border-slate-200 bg-white p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
      <UserAvatar
        :src="user?.avatar_url || ''"
        :name="user?.full_name || user?.name || 'User'"
        :first-name="user?.first_name || ''"
        :last-name="user?.last_name || ''"
        size="lg"
      />
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="truncate text-xl font-semibold text-slate-900">{{ user?.full_name }}</h2>
          <StatusBadge :status="user?.status" />
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ user?.email }}</p>
        <div v-if="roles.length" class="mt-2 flex flex-wrap gap-1.5">
          <RoleBadge
            v-for="role in roles"
            :key="role.uuid || role.name"
            :name="role.name"
            :display-name="role.display_name"
            :system="Boolean(role.is_system)"
          />
        </div>
        <p v-else class="mt-2 text-xs text-slate-400">No role assigned</p>
      </div>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ user?.phone || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Timezone</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ user?.timezone || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Language</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ user?.language || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Last login</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ formatDate(user?.last_login_at) || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ formatDate(user?.created_at) || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ user?.created_by?.full_name || '—' }}</dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';
import StatusBadge from '@/modules/users/components/StatusBadge.vue';
import UserAvatar from '@/components/ui/UserAvatar.vue';

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

const roles = computed(() => props.user?.roles || []);
</script>
