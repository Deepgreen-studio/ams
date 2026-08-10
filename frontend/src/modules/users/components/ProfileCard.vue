<template>
  <div :class="embedded ? '' : 'rounded-[12px] bg-white p-6'">
    <div v-if="!hideAvatar" class="flex flex-col gap-4 sm:flex-row sm:items-center">
      <UserAvatar
        :src="avatarSrc"
        :name="user?.full_name || user?.name || 'User'"
        :first-name="user?.first_name || ''"
        :last-name="user?.last_name || ''"
        size="lg"
      />
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
            {{ user?.full_name }}
          </h2>
          <StatusBadge :status="user?.status" />
        </div>
        <p class="mt-1 truncate text-sm text-slate-500">{{ user?.email }}</p>
        <div v-if="roles.length" class="mt-2.5 flex flex-wrap gap-1.5">
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

    <div v-else class="text-center">
      <div class="flex flex-wrap items-center justify-center gap-2">
        <h2 class="truncate text-lg font-semibold tracking-tight text-slate-900">
          {{ user?.full_name }}
        </h2>
        <StatusBadge :status="user?.status" />
      </div>
      <p class="mt-1 truncate text-sm text-slate-500">{{ user?.email }}</p>
      <div v-if="roles.length" class="mt-2.5 flex flex-wrap justify-center gap-1.5">
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

    <div :class="hideAvatar || !embedded ? 'mt-6' : 'mt-5'">
      <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
        Account details
      </p>
      <dl class="divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
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
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';
import { getUserAvatarUrl } from '@/utils/avatar';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';
import StatusBadge from '@/modules/users/components/StatusBadge.vue';
import UserAvatar from '@/components/ui/UserAvatar.vue';

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  embedded: {
    type: Boolean,
    default: false,
  },
  hideAvatar: {
    type: Boolean,
    default: false,
  },
});

const roles = computed(() => props.user?.roles || []);
const avatarSrc = computed(() => getUserAvatarUrl(props.user));

const detailItems = computed(() => [
  { label: 'Phone', value: props.user?.phone || '—' },
  { label: 'Timezone', value: props.user?.timezone || '—' },
  { label: 'Language', value: props.user?.language || '—' },
  { label: 'Last login', value: formatDate(props.user?.last_login_at) || '—' },
  { label: 'Created', value: formatDate(props.user?.created_at) || '—' },
  { label: 'Created by', value: props.user?.created_by?.full_name || '—' },
]);
</script>
