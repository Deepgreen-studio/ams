<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto px-3">
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Created At</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'deleted_at')"
              >
                Deleted
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'deleted_at' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th
              v-if="hasAnyAction"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr v-for="n in 5" :key="n">
            <td colspan="12" class="px-5 py-3">
              <div class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="!roles.length">

          <tr>

            <td colspan="12" class="p-0">
              <EmptyState
                title="Trash is empty"
                description="Soft-deleted roles will appear here."
                >
                <template #action>
                <slot name="empty-action" />
                </template>
              </EmptyState>
            </td>

          </tr>

        </tbody>

        <tbody v-else>
          <tr
            v-for="role in roles"
            :key="role.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            :class="can('roles.view') ? 'cursor-pointer' : ''"
            @click="openDetails(role)"
          >
            <td class="px-5 py-4">
              <p class="truncate font-semibold text-slate-900">{{ role.display_name }}</p>
            </td>
            <td class="px-5 py-4 text-slate-600">{{ role.permissions_count ?? 0 }}</td>
            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
              {{ formatDate(role.created_at) || '—' }}
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ formatDate(role.deleted_at) }}
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === role.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(role.uuid, $event)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-5">
      <slot name="footer" />
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeRole"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          v-if="can('roles.restore')"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onRestore(activeRole)"
        >
          <ArrowUturnLeftIcon class="h-4 w-4 text-slate-400" />
          Restore
        </button>
        <button
          v-if="can('roles.force-delete') && !activeRole.is_system"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onForceDelete(activeRole)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Force Delete
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  ArrowUturnLeftIcon,
  EllipsisVerticalIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatDate } from '@/utils/formatters';

const props = defineProps({
  roles: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  sortBy: { type: String, default: 'deleted_at' },
  sortDir: { type: String, default: 'desc' },
});

const emit = defineEmits(['sort', 'restore', 'force-delete']);

const { can, canAny } = usePermissions();
const router = useRouter();

function openDetails(role) {
  if (!role?.uuid || !can('roles.view')) {
    return;
  }

  router.push({ name: 'roles.show', params: { id: role.uuid } });
}
const hasAnyAction = computed(() =>
  canAny('roles.restore', 'roles.force-delete'),
);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeRole = computed(
  () => props.roles.find((role) => role.uuid === openMenuId.value) || null,
);

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const role = props.roles.find((item) => item.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const itemCount = [
    can('roles.restore'),
    can('roles.force-delete') && role && !role.is_system,
  ].filter(Boolean).length;
  const menuHeight = 8 + Math.max(itemCount, 1) * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onRestore(role) {
  closeMenu();
  emit('restore', role);
}

function onForceDelete(role) {
  closeMenu();
  emit('force-delete', role);
}

function onDocumentClick() {
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize);
});
</script>
