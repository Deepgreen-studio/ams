<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!releases.length"
      title="No releases"
      description="Create a release plan linked to an application version."
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Release</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Version</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Type
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Schedule
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in releases"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <p class="font-semibold text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.environment?.name || 'No environment' }}</p>
            </td>
            <td class="px-5 py-4 text-slate-700">{{ item.version_label || '—' }}</td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ item.release_type_label || item.release_type || '—' }}
            </td>
            <td class="px-5 py-4">
              <ReleaseStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ formatDate(item.scheduled_at || item.deployment_date) }}
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

    <Teleport to="body">
      <div
        v-if="openMenuId && activeRelease"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          :to="{
            name: 'applications.releases.show',
            params: { id: applicationId, releaseId: activeRelease.uuid },
          }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          Details
        </RouterLink>
        <RouterLink
          v-if="activeRelease.approval_status === 'pending'"
          :to="{
            name: 'applications.releases.approval',
            params: { id: applicationId, releaseId: activeRelease.uuid },
          }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-amber-700 transition hover:bg-amber-50"
          role="menuitem"
          @click="closeMenu"
        >
          <CheckBadgeIcon class="h-4 w-4 text-amber-500" />
          Approve
        </RouterLink>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  CheckBadgeIcon,
  EllipsisVerticalIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import ReleaseStatusBadge from '@/modules/applications/components/ReleaseStatusBadge.vue';

const props = defineProps({
  applicationId: { type: String, required: true },
  releases: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const openMenuId = ref(null);
const menuStyle = ref({});

const activeRelease = computed(
  () => props.releases.find((item) => item.uuid === openMenuId.value) || null,
);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 96;
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
