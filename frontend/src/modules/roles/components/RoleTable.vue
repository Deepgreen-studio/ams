<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!roles.length"
      title="No roles found"
      description="Try adjusting your search or create a new role."
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
                @click="$emit('sort', 'display_name')"
              >
                Role
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'display_name' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Permissions</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Users
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="role in roles"
            :key="role.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <p class="truncate font-semibold text-slate-900">{{ role.display_name }}</p>
            </td>
            <td class="px-5 py-4 text-slate-600">{{ role.permissions_count ?? 0 }}</td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ role.users_count ?? 0 }}
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === role.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(role.uuid)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>

                <div
                  v-if="openMenuId === role.uuid"
                  class="absolute right-0 top-10 z-20 w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
                  role="menu"
                >
                  <RouterLink
                    :to="{ name: 'roles.show', params: { id: role.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <EyeIcon class="h-4 w-4 text-slate-400" />
                    View
                  </RouterLink>
                  <RouterLink
                    :to="{ name: 'roles.edit', params: { id: role.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <PencilSquareIcon class="h-4 w-4 text-slate-400" />
                    Edit
                  </RouterLink>
                  <RouterLink
                    :to="{ name: 'roles.permissions', params: { id: role.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <KeyIcon class="h-4 w-4 text-slate-400" />
                    Permissions
                  </RouterLink>
                  <button
                    v-if="!role.is_system"
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                    role="menuitem"
                    @click="onDelete(role)"
                  >
                    <TrashIcon class="h-4 w-4 text-red-500" />
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
import {
  EllipsisVerticalIcon,
  EyeIcon,
  KeyIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  sortBy: {
    type: String,
    default: 'name',
  },
  sortDir: {
    type: String,
    default: 'asc',
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

function onDelete(role) {
  closeMenu();
  emit('delete', role);
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
