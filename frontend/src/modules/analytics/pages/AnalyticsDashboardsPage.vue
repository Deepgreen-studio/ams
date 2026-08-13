<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.templates' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <RectangleStackIcon class="h-4 w-4" />
        Templates
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="showCreate = true"
      >
        <PlusIcon class="h-4 w-4" />
        Create dashboard
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
          @submit.prevent="onApply"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="local.search"
              type="search"
              placeholder="Search name or description…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="local.category"
              wrapper-class="min-w-[12rem]"
              :options="categorySelectOptions"
            />
            <SelectBox
              v-model="local.status"
              wrapper-class="min-w-[10rem]"
              :options="statusSelectOptions"
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
              @click="onReset"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      <div v-if="store.loading && !store.dashboards.length" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.dashboards.length"
        title="No dashboards found"
        description="Try adjusting your filters or create a new analytics dashboard."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset filters
          </button>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="showCreate = true"
          >
            Create dashboard
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Name</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Category</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Widgets
              </th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.dashboards"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <RouterLink
                  :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
                  class="font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.name }}
                </RouterLink>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.description || item.slug }}</p>
              </td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ item.category }}</td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                    :class="statusTone(item.status)"
                  >
                    {{ item.status }}
                  </span>
                  <span
                    v-if="item.is_system"
                    class="inline-flex items-center rounded-full bg-zinc-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-zinc-200"
                  >
                    System
                  </span>
                </div>
              </td>
              <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
                {{ item.widgets_count ?? 0 }}
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

      <div v-if="store.dashboardsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.dashboardsMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeDashboard"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          :to="{ name: 'analytics.dashboards.show', params: { uuid: activeDashboard.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </RouterLink>
        <RouterLink
          :to="{ name: 'analytics.dashboards.designer', params: { uuid: activeDashboard.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Design
        </RouterLink>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onDuplicate(activeDashboard)"
        >
          <DocumentDuplicateIcon class="h-4 w-4 text-slate-400" />
          Duplicate
        </button>
        <button
          v-if="!activeDashboard.is_system"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activeDashboard)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>

    <div
      v-if="showCreate"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-lg overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h3 class="text-base font-semibold text-slate-900">Create dashboard</h3>
          <p class="mt-0.5 text-xs text-slate-500">Configure a reusable analytics board and open the designer.</p>
        </div>
        <form class="space-y-4 px-6 py-5" @submit.prevent="onCreate">
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
            <input v-model="form.name" required class="input" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
            <SelectBox v-model="form.category" :options="categoryOptions" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
            <textarea v-model="form.description" rows="3" class="input" />
          </div>
          <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="showCreate = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              Create
            </button>
          </div>
        </form>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete dashboard"
      :message="`Delete dashboard “${pendingDelete?.name}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  DocumentDuplicateIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusIcon,
  RectangleStackIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const store = useEnterpriseAnalyticsStore();
const router = useRouter();
const toast = useToast();
const showCreate = ref(false);
const pendingDelete = ref(null);
const perPage = ref(10);
const openMenuId = ref(null);
const menuStyle = ref({});

const local = reactive({
  search: '',
  category: '',
  status: '',
});

const form = reactive({
  name: '',
  category: 'business',
  description: '',
  status: 'published',
  kind: 'dashboard',
  visibility: 'personal',
});

const categoryOptions = computed(() =>
  store.categories.length
    ? store.categories
    : [
        { value: 'business', label: 'Business Analytics' },
        { value: 'operational', label: 'Operational Analytics' },
        { value: 'application', label: 'Application Analytics' },
        { value: 'customer', label: 'Customer Analytics' },
        { value: 'api', label: 'API Analytics' },
        { value: 'system', label: 'System Analytics' },
      ],
);

const categorySelectOptions = computed(() => [
  { value: '', label: 'All categories' },
  ...categoryOptions.value,
]);

const statusSelectOptions = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'archived', label: 'Archived' },
];

const activeDashboard = computed(
  () => store.dashboards.find((item) => item.uuid === openMenuId.value) || null,
);

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

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);

  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await loadDashboards(1);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});

function listParams(page = 1) {
  return {
    kind: 'dashboard',
    is_template: false,
    search: local.search,
    category: local.category,
    status: local.status,
    from: '',
    to: '',
    page,
    per_page: perPage.value,
  };
}

async function loadDashboards(page = 1) {
  await store.fetchDashboards(listParams(page));
}

function onApply() {
  loadDashboards(1).catch(() => {});
}

function onReset() {
  local.search = '';
  local.category = '';
  local.status = '';
  loadDashboards(1).catch(() => {});
}

function onPageChange(page) {
  loadDashboards(page).catch(() => {});
}

function onPerPage(value) {
  perPage.value = value;
  loadDashboards(1).catch(() => {});
}

function statusTone(status) {
  if (status === 'published') {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
  }
  if (status === 'archived') {
    return 'bg-zinc-50 text-slate-600 ring-zinc-200';
  }
  return 'bg-slate-50 text-slate-700 ring-slate-500/20';
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 8 + 4 * 36;
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

async function onCreate() {
  const dashboard = await store.createDashboard({ ...form });
  showCreate.value = false;
  form.name = '';
  form.description = '';
  await loadDashboards(store.dashboardsMeta?.current_page || 1);
  if (dashboard?.uuid) {
    router.push({ name: 'analytics.dashboards.designer', params: { uuid: dashboard.uuid } });
  }
}

async function onDuplicate(item) {
  closeMenu();
  const copy = await store.duplicateDashboard(item.uuid);
  await loadDashboards(store.dashboardsMeta?.current_page || 1);
  if (copy?.uuid) {
    router.push({ name: 'analytics.dashboards.show', params: { uuid: copy.uuid } });
  }
}

function onDelete(item) {
  closeMenu();
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  try {
    await store.deleteDashboard(pendingDelete.value.uuid);
    pendingDelete.value = null;
    await loadDashboards(1);
  } catch {
    pendingDelete.value = null;
  }
}
</script>
