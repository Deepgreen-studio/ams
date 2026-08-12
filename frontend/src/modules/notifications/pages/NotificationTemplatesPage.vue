<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="can('notifications.approve')"
        :to="{ name: 'notifications.templates.approvals' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Approvals
      </RouterLink>
      <RouterLink
        v-if="can('notifications.create')"
        :to="{ name: 'notifications.templates.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        New template
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search templates…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.channel"
              wrapper-class="min-w-[9.5rem]"
              :options="channelOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.locale"
              wrapper-class="min-w-[9.5rem]"
              :options="localeOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.workflow_status"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.templates.length"
        title="No templates found"
        description="Try adjusting your filters or create a new notification template."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
          <RouterLink
            v-if="can('notifications.create')"
            :to="{ name: 'notifications.templates.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            New template
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Template</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Channel</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Locale</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Workflow</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Version</th>
              <th
                v-if="hasAnyAction"
                class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
              >
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.templates"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ item.event_label }} · {{ item.subject || 'No subject' }}
                </p>
              </td>
              <td class="px-5 py-4">
                <span class="inline-flex rounded-[8px] bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                  {{ item.channel_label }}
                </span>
              </td>
              <td class="px-5 py-4 uppercase text-slate-600">{{ item.locale }}</td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="workflowClass(item.workflow_status)"
                >
                  {{ item.workflow_status_label }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">v{{ item.current_version }}</td>
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

      <div
        v-if="store.templateMeta?.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination :meta="store.templateMeta" @change="onPageChange" />
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeTemplate"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          v-if="can('notifications.update')"
          :to="{ name: 'notifications.templates.edit', params: { id: activeTemplate.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </RouterLink>
        <RouterLink
          v-if="can('notifications.view')"
          :to="{ name: 'notifications.templates.preview', params: { id: activeTemplate.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          Preview
        </RouterLink>
        <RouterLink
          v-if="can('notifications.view')"
          :to="{ name: 'notifications.templates.versions', params: { id: activeTemplate.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <ClockIcon class="h-4 w-4 text-slate-400" />
          Versions
        </RouterLink>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ClockIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const { can, canAny } = usePermissions();
const hasAnyAction = computed(() =>
  canAny('notifications.view', 'notifications.update'),
);

const filters = reactive({
  search: '',
  channel: '',
  locale: '',
  workflow_status: '',
  page: 1,
  per_page: 20,
});

const openMenuId = ref(null);
const menuStyle = ref({});

const activeTemplate = computed(
  () => store.templates.find((item) => item.uuid === openMenuId.value) || null,
);

const channelOptions = computed(() => [
  { value: '', label: 'All channels' },
  ...(store.templateChannels || []).map((channel) => ({
    value: channel.value,
    label: channel.label,
  })),
]);

const localeOptions = computed(() => [
  { value: '', label: 'All locales' },
  ...(store.templateLocales || []).map((locale) => ({
    value: locale.value,
    label: locale.label,
  })),
]);

const statusOptions = computed(() => [
  { value: '', label: 'All statuses' },
  ...(store.templateWorkflowStatuses || []).map((status) => ({
    value: status.value,
    label: status.label,
  })),
]);

onMounted(() => {
  reload();
  document.addEventListener('click', onDocumentClick);
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize);
});

async function reload() {
  await store.fetchTemplates({
    search: filters.search || undefined,
    channel: filters.channel || undefined,
    locale: filters.locale || undefined,
    workflow_status: filters.workflow_status || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  reload();
}

function resetFilters() {
  filters.search = '';
  filters.channel = '';
  filters.locale = '';
  filters.workflow_status = '';
  filters.page = 1;
  reload();
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 132;
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

function workflowClass(status) {
  if (status === 'published') return 'bg-emerald-50 text-emerald-700';
  if (status === 'pending_review' || status === 'in_review') return 'bg-amber-50 text-amber-700';
  if (status === 'draft') return 'bg-zinc-100 text-slate-600';
  if (status === 'rejected' || status === 'archived') return 'bg-rose-50 text-rose-700';
  return 'bg-zinc-100 text-slate-600';
}
</script>
