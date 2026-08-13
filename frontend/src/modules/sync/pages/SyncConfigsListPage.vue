<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'sync.configs.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create config
      </RouterLink>
    </Teleport>

    <SyncSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
          @submit.prevent="applyFilters"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search name, slug, entity…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.direction"
              wrapper-class="min-w-[9.5rem]"
              :options="directionOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.trigger_type"
              wrapper-class="min-w-[9.5rem]"
              :options="triggerOptions"
              @change="applyFilters"
            />
            <button
              type="submit"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
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
        </form>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.configs.length"
        title="No sync configs found"
        description="Try adjusting your filters or create a new synchronization config."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset filters
          </button>
          <RouterLink
            :to="{ name: 'sync.configs.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create config
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Config</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Direction</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Trigger</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.configs"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ item.integration?.name || '—' }} · {{ item.company?.company_name || '—' }}
                </p>
              </td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ item.direction }}</td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ item.trigger_type }}</td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
                  :class="
                    item.is_enabled
                      ? 'border-emerald-600 text-emerald-700'
                      : 'border-slate-300 text-slate-500'
                  "
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="item.is_enabled ? 'bg-emerald-600' : 'bg-slate-400'"
                  />
                  {{ item.is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
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

      <div v-if="store.configsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.configsMeta"
          :loading="store.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeConfig"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          :to="{ name: 'sync.configs.show', params: { id: activeConfig.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </RouterLink>
        <RouterLink
          :to="{ name: 'sync.configs.edit', params: { id: activeConfig.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </RouterLink>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activeConfig)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete sync config"
      :message="`Delete “${pendingDelete?.name || 'this config'}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="closeDelete"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  EllipsisVerticalIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const store = useSyncStore();
const toast = useToast();
const openMenuId = ref(null);
const menuStyle = ref({});
const showDelete = ref(false);
const pendingDelete = ref(null);
const filters = reactive({
  search: '',
  direction: '',
  trigger_type: '',
  page: 1,
  per_page: 10,
});

const activeConfig = computed(
  () => store.configs.find((item) => item.uuid === openMenuId.value) || null,
);

const directionOptions = [
  { value: '', label: 'All directions' },
  { value: 'import', label: 'Import' },
  { value: 'export', label: 'Export' },
  { value: 'bidirectional', label: 'Bidirectional' },
];

const triggerOptions = [
  { value: '', label: 'All triggers' },
  { value: 'manual', label: 'Manual' },
  { value: 'automatic', label: 'Automatic' },
  { value: 'scheduled', label: 'Scheduled' },
];

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  load();
  document.addEventListener('click', onDocumentClick);
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize);
});

function load() {
  store.fetchConfigs({ ...filters });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.direction = '';
  filters.trigger_type = '';
  filters.page = 1;
  load();
}

function onPage(page) {
  filters.page = page;
  load();
}

function onPerPage(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 8 + 3 * 36;
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

function onDelete(item) {
  closeMenu();
  pendingDelete.value = item;
  showDelete.value = true;
}

function closeDelete() {
  showDelete.value = false;
  pendingDelete.value = null;
}

async function confirmDelete() {
  const item = pendingDelete.value;
  if (!item) return;
  await store.deleteConfig(item.uuid);
  closeDelete();
  await load();
}

function onDocumentClick() {
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
}
</script>
