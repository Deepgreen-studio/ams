<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!users.length"
      title="No users found"
      description="Try adjusting your search or create a new user."
      class="px-8 py-6"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'full_name')"
              >
                Name
                <span class="text-[10px] leading-none text-zinc-300">
                  {{ sortBy === 'full_name' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'email')"
              >
                Email
                <span class="text-[10px] leading-none text-zinc-300">
                  {{ sortBy === 'email' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Phone
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'status')"
              >
                Status
                <span class="text-[10px] leading-none text-zinc-300">
                  {{ sortBy === 'status' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'created_at')"
              >
                Created
                <span class="text-[10px] leading-none text-zinc-300">
                  {{ sortBy === 'created_at' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in users"
            :key="user.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <UserAvatar
                  :src="user.avatar_url || ''"
                  :name="user.full_name || user.name || 'User'"
                  :first-name="user.first_name || ''"
                  :last-name="user.last_name || ''"
                  size="sm"
                  class="!rounded-[12px]"
                />
                <div class="min-w-0">
                  <p class="truncate font-semibold text-slate-900">{{ user.full_name }}</p>
                  <p class="truncate text-xs text-zinc-400">{{ user.uuid }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-4 text-slate-600">{{ user.email }}</td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">{{ user.phone || '—' }}</td>
            <td class="px-5 py-4">
              <StatusBadge :status="user.status" />
            </td>
            <td class="hidden px-5 py-4 lg:table-cell">
              <p class="font-medium text-slate-800">{{ formatDate(user.created_at) }}</p>
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === user.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(user.uuid)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>

                <div
                  v-if="openMenuId === user.uuid"
                  class="absolute right-0 top-10 z-20 w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
                  role="menu"
                >
                  <RouterLink
                    :to="{ name: 'users.show', params: { id: user.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <EyeIcon class="h-4 w-4 text-slate-400" />
                    View
                  </RouterLink>
                  <RouterLink
                    :to="{ name: 'users.edit', params: { id: user.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <PencilSquareIcon class="h-4 w-4 text-slate-400" />
                    Edit
                  </RouterLink>
                  <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                    role="menuitem"
                    @click="onDelete(user)"
                  >
                    <TrashIcon class="h-4 w-4" />
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-5">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { EllipsisVerticalIcon, EyeIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import UserAvatar from '@/components/ui/UserAvatar.vue';
import { formatDate } from '@/utils/formatters';
import StatusBadge from '@/modules/users/components/StatusBadge.vue';

defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  sortBy: {
    type: String,
    default: 'created_at',
  },
  sortDir: {
    type: String,
    default: 'desc',
  },
});

const emit = defineEmits(['sort', 'delete']);

const openMenuId = ref(null);

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onDelete(user) {
  closeMenu();
  emit('delete', user);
}

function onDocumentClick() {
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>
