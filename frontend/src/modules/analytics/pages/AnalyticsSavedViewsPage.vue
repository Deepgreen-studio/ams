<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="showCreate = true"
      >
        <BookmarkIcon class="h-4 w-4" />
        Save current filters
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="store.categories"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !store.savedViews.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-48 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!store.savedViews.length"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="No saved views yet"
        description="Save your current date range and category to reopen this analytics slice later."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="showCreate = true"
          >
            Save current filters
          </button>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="item in store.savedViews"
        :key="item.uuid"
        class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <RouterLink
              :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
              class="text-sm font-semibold text-slate-900 hover:text-brand-700"
            >
              {{ item.name }}
            </RouterLink>
            <div class="mt-2">
              <span
                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                :class="categoryClasses(item.category || item.filters?.category)"
              >
                {{ formatLabel(item.category || item.filters?.category) || 'All categories' }}
              </span>
            </div>
          </div>
          <button
            type="button"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
            :aria-expanded="openMenuId === item.uuid"
            aria-haspopup="menu"
            aria-label="Open actions"
            @click.stop="toggleMenu(item.uuid, $event)"
          >
            <EllipsisVerticalIcon class="h-5 w-5" />
          </button>
        </div>

        <dl class="mt-4 space-y-1.5 text-xs text-slate-600">
          <div class="flex justify-between gap-2">
            <dt class="text-slate-400">From</dt>
            <dd>{{ formatDate(item.filters?.from) }}</dd>
          </div>
          <div class="flex justify-between gap-2">
            <dt class="text-slate-400">To</dt>
            <dd>{{ formatDate(item.filters?.to) }}</dd>
          </div>
          <div class="flex justify-between gap-2">
            <dt class="text-slate-400">Category</dt>
            <dd>{{ formatLabel(item.filters?.category) || 'All' }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeView"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          :to="{ name: 'analytics.dashboards.show', params: { uuid: activeView.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          Open
        </RouterLink>
        <button
          v-if="!activeView.is_system"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
          role="menuitem"
          @click="onDelete(activeView)"
        >
          <TrashIcon class="h-4 w-4 text-rose-500" />
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
          <h3 class="text-base font-semibold text-slate-900">Save view</h3>
          <p class="mt-0.5 text-xs text-slate-500">Store the current date range and category as a reusable preset.</p>
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
              Save view
            </button>
          </div>
        </form>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete saved view"
      :message="`Delete saved view “${pendingDelete?.name}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { BookmarkIcon, EllipsisVerticalIcon, EyeIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const store = useEnterpriseAnalyticsStore();
const toast = useToast();
const showCreate = ref(false);
const pendingDelete = ref(null);
const openMenuId = ref(null);
const menuStyle = ref({});
const form = reactive({
  name: '',
  category: 'operational',
});

const categoryStyles = {
  business: 'bg-sky-50 text-sky-700 ring-sky-600/20',
  customer: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
  application: 'bg-amber-50 text-amber-800 ring-amber-600/20',
  api: 'bg-violet-50 text-violet-700 ring-violet-600/20',
  operational: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  security: 'bg-rose-50 text-rose-700 ring-rose-600/20',
  executive: 'bg-brand-50 text-brand-700 ring-brand-200',
};

const categoryOptions = computed(() =>
  store.categories.length
    ? store.categories
    : [{ value: 'operational', label: 'Operational Analytics' }],
);

const activeView = computed(
  () => store.savedViews.find((item) => item.uuid === openMenuId.value) || null,
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
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);
  store.successMessage = null;
  store.error = null;
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await store.fetchSavedViews();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});

function categoryClasses(category) {
  return categoryStyles[String(category || '').toLowerCase()] || 'bg-zinc-100 text-zinc-700 ring-zinc-500/15';
}

function formatLabel(value) {
  if (!value) {
    return '';
  }

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDate(value) {
  if (!value) {
    return '—';
  }

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleDateString();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const item = store.savedViews.find((view) => view.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = item?.is_system ? 44 : 80;
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
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchSavedViews();
}

async function onCreate() {
  await store.createSavedView({
    name: form.name,
    category: form.category,
    status: 'published',
    filters: {
      from: store.filters.from,
      to: store.filters.to,
      category: store.filters.category || form.category,
    },
  });
  showCreate.value = false;
  form.name = '';
  await store.fetchSavedViews();
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  try {
    await store.deleteDashboard(pendingDelete.value.uuid);
    pendingDelete.value = null;
  } catch {
    pendingDelete.value = null;
  }
}
</script>
