<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Environment</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Type
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              API URL
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
              Web URL
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Health
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Vars
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr v-for="n in 3" :key="n">
            <td colspan="8" class="px-5 py-3">
              <div class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="!environments.length">
          <tr>
            <td colspan="8" class="p-0">
              <EmptyState
                title="No environments"
                description="Create Development, Testing, Staging, Production, or Sandbox environments."
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
            v-for="item in environments"
            :key="item.uuid"
            class="cursor-pointer border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            @click="openDetails(item)"
          >
            <td class="px-5 py-4">
              <div class="flex flex-wrap items-center gap-2">
                <p class="font-semibold text-slate-900">{{ item.name }}</p>
                <span
                  v-if="item.is_current"
                  class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700"
                >
                  Current
                </span>
              </div>
              <p class="text-xs text-slate-500 md:hidden">
                {{ item.type_label || item.type || '—' }}
              </p>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ item.type_label || item.type || '—' }}
            </td>
            <td class="hidden max-w-[14rem] truncate px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.api_url || '—' }}
            </td>
            <td class="hidden max-w-[14rem] truncate px-5 py-4 text-slate-600 xl:table-cell">
              {{ item.web_url || '—' }}
            </td>
            <td class="px-5 py-4">
              <EnvironmentHealthBadge :status="item.status" kind="status" />
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <EnvironmentHealthBadge :status="item.health_status" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ variableCount(item) }}
            </td>
            <td class="px-5 py-4">
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
        v-if="openMenuId && activeEnvironment"
        class="fixed z-[80] w-48 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          :to="detailsRoute(activeEnvironment)"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          Details
        </RouterLink>
        <button
          v-if="canUpdate && !activeEnvironment.is_current"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50 disabled:opacity-60"
          role="menuitem"
          :disabled="busy"
          @click="onSwitch(activeEnvironment)"
        >
          <ArrowPathIcon class="h-4 w-4 text-slate-400" />
          Switch
        </button>
        <button
          v-if="canUpdate"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50 disabled:opacity-60"
          role="menuitem"
          :disabled="busy"
          @click="onHealthCheck(activeEnvironment)"
        >
          <HeartIcon class="h-4 w-4 text-slate-400" />
          Health Check
        </button>
        <RouterLink
          v-if="canUpdate"
          :to="editRoute(activeEnvironment)"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </RouterLink>
        <button
          v-if="canUpdate"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activeEnvironment)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  ArrowPathIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  HeartIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import EnvironmentHealthBadge from '@/modules/applications/components/EnvironmentHealthBadge.vue';

const props = defineProps({
  applicationId: { type: String, required: true },
  environments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  busy: { type: Boolean, default: false },
});

const emit = defineEmits(['switch', 'health-check', 'delete']);

const router = useRouter();
const { can } = usePermissions();
const canUpdate = computed(() => can('applications.update'));

const openMenuId = ref(null);
const menuStyle = ref({});

const activeEnvironment = computed(
  () => props.environments.find((item) => item.uuid === openMenuId.value) || null,
);

function variableCount(item) {
  if (Array.isArray(item?.variables)) return item.variables.length;
  if (Array.isArray(item?.variable_keys)) return item.variable_keys.length;
  return 0;
}

function detailsRoute(environment) {
  return {
    name: 'applications.environments.show',
    params: { id: props.applicationId, environmentId: environment.uuid },
  };
}

function editRoute(environment) {
  return {
    name: 'applications.environments.edit',
    params: { id: props.applicationId, environmentId: environment.uuid },
  };
}

function openDetails(environment) {
  if (!environment?.uuid || !props.applicationId) return;
  router.push(detailsRoute(environment));
}

function menuItemCount(environment) {
  let count = 1;
  if (canUpdate.value) {
    count += environment?.is_current ? 3 : 4;
  }
  return count;
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const environment = props.environments.find((item) => item.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 192;
  const menuHeight = 8 + menuItemCount(environment) * 36;
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

function onSwitch(environment) {
  closeMenu();
  emit('switch', environment);
}

function onHealthCheck(environment) {
  closeMenu();
  emit('health-check', environment);
}

function onDelete(environment) {
  closeMenu();
  emit('delete', environment);
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
