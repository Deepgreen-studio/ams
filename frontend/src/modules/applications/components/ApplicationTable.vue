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
                @click="$emit('sort', 'name')"
              >
                Application
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'name' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'platform')"
              >
                Platform
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'platform' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'category')"
              >
                Category
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'category' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'status')"
              >
                Status
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'status' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'current_version')"
              >
                Version
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'current_version' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'created_at')"
              >
                Created At
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'created_at' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
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
        <tbody v-else-if="!applications.length">

          <tr>

            <td colspan="12" class="p-0">
              <EmptyState
                title="No applications found"
                description="No results match the current search."
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
            v-for="item in applications"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            :class="can('applications.view') ? 'cursor-pointer' : ''"
            @click="openDetails(item)"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  <img
                    v-if="iconSrc(item) && !failedIcons[item.uuid]"
                    :src="iconSrc(item)"
                    alt=""
                    class="h-full w-full object-cover"
                    @error="markIconFailed(item.uuid)"
                  />
                  <span v-else>{{ initials(item.name) }}</span>
                </div>
                <div class="min-w-0">
                  <p class="truncate font-semibold text-slate-900">{{ item.name }}</p>
                  <p class="truncate text-xs text-slate-500">
                    {{ item.slug }} · {{ item.company?.company_name || '—' }}
                  </p>
                </div>
              </div>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <div class="flex flex-wrap gap-1.5">
                <StatusBadge
                  v-for="platform in applicationPlatforms(item)"
                  :key="platform"
                  :status="platform"
                  kind="platform"
                />
              </div>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.category_label || item.category || '—' }}
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.status" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.current_version || '—' }}
            </td>
            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
              {{ formatDate(item.created_at) || '—' }}
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === item.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(item.uuid, $event)"
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
        v-if="openMenuId && activeApplication"
        class="fixed z-[80] w-48 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          v-if="can('applications.view') && !isTrashed(activeApplication)"
          :to="{ name: 'applications.show', params: { id: activeApplication.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </RouterLink>
        <template v-if="isTrashed(activeApplication)">
          <button
            v-if="can('applications.restore')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="onRestore(activeApplication)"
          >
            <ArrowUturnLeftIcon class="h-4 w-4 text-slate-400" />
            Restore
          </button>
          <button
            v-if="can('applications.force-delete')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
            role="menuitem"
            @click="onForceDelete(activeApplication)"
          >
            <TrashIcon class="h-4 w-4 text-red-500" />
            Permanent Delete
          </button>
        </template>
        <template v-else>
          <RouterLink
            v-if="can('applications.update')"
            :to="{ name: 'applications.edit', params: { id: activeApplication.uuid } }"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="closeMenu"
          >
            <PencilSquareIcon class="h-4 w-4 text-slate-400" />
            Edit
          </RouterLink>
          <button
            v-if="can('applications.delete')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
            role="menuitem"
            @click="onDelete(activeApplication)"
          >
            <TrashIcon class="h-4 w-4 text-red-500" />
            Soft Delete
          </button>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  ArrowUturnLeftIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { formatDate } from '@/utils/formatters';
import { usePermissions } from '@/composables/usePermissions';
import StatusBadge from '@/modules/applications/components/StatusBadge.vue';
import { applicationPlatforms } from '@/modules/applications/utils/platforms';
import { resolveMediaUrl } from '@/utils/mediaUrl';

const props = defineProps({
  applications: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  sortBy: { type: String, default: 'created_at' },
  sortDir: { type: String, default: 'desc' },
});

const emit = defineEmits(['sort', 'delete', 'restore', 'force-delete']);

const { can, canAny } = usePermissions();
const router = useRouter();

function openDetails(application) {
  if (!application?.uuid || !can('applications.view') || isTrashed(application)) {
    return;
  }

  router.push({ name: 'applications.show', params: { id: application.uuid } });
}
const hasAnyAction = computed(() =>
  canAny(
    'applications.view',
    'applications.update',
    'applications.delete',
    'applications.restore',
    'applications.force-delete',
  ),
);

const openMenuId = ref(null);
const menuStyle = ref({});
const failedIcons = reactive({});

const activeApplication = computed(
  () => props.applications.find((item) => item.uuid === openMenuId.value) || null,
);

function isTrashed(application) {
  return Boolean(application?.deleted_at);
}

function initials(name) {
  return String(name || 'A')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function iconSrc(item) {
  return resolveMediaUrl(item?.icon || '');
}

function markIconFailed(uuid) {
  failedIcons[uuid] = true;
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const application = props.applications.find((item) => item.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 192;
  const itemCount = isTrashed(application)
    ? [can('applications.restore'), can('applications.force-delete')].filter(Boolean).length
    : [can('applications.view'), can('applications.update'), can('applications.delete')].filter(Boolean).length;
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

function onDelete(application) {
  closeMenu();
  emit('delete', application);
}

function onForceDelete(application) {
  closeMenu();
  emit('force-delete', application);
}

function onRestore(application) {
  closeMenu();
  emit('restore', application);
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
