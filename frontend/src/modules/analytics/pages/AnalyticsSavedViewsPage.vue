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

    <EmptyState
      v-else-if="!store.savedViews.length"
      title="No saved views yet"
      description="Save your current date and category filters to reuse them later."
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

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="item in store.savedViews"
        :key="item.uuid"
        class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="mt-1 text-xs text-slate-500 capitalize">{{ item.category }}</p>
          </div>
          <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-medium uppercase text-slate-600">
            saved view
          </span>
        </div>
        <dl class="mt-4 space-y-1 text-xs text-slate-600">
          <div class="flex justify-between gap-2">
            <dt>From</dt>
            <dd>{{ item.filters?.from || '—' }}</dd>
          </div>
          <div class="flex justify-between gap-2">
            <dt>To</dt>
            <dd>{{ item.filters?.to || '—' }}</dd>
          </div>
          <div class="flex justify-between gap-2">
            <dt>Category filter</dt>
            <dd class="capitalize">{{ item.filters?.category || 'all' }}</dd>
          </div>
        </dl>
        <div class="mt-4 flex gap-2">
          <RouterLink
            :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
            class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
          >
            Open
          </RouterLink>
          <button
            v-if="!item.is_system"
            type="button"
            class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
            @click="pendingDelete = item"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { BookmarkIcon } from '@heroicons/vue/24/outline';
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
const form = reactive({
  name: '',
  category: 'operational',
});

const categoryOptions = computed(() =>
  store.categories.length
    ? store.categories
    : [{ value: 'operational', label: 'Operational Analytics' }]
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

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await store.fetchSavedViews();
});
</script>
