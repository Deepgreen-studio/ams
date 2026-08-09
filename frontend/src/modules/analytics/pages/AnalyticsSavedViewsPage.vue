<template>
  <div>
    <!-- <PageHeader title="Saved Views" description="Reusable filter presets for enterprise analytics dashboards.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="showCreate = true"
        >
          Save current filters
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="showCreate = true"
        >
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

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="item in store.savedViews"
        :key="item.uuid"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="mt-1 text-xs text-slate-500 capitalize">{{ item.category }}</p>
          </div>
          <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-medium uppercase text-slate-600">
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
        <div class="mt-4 flex gap-3">
          <RouterLink
            :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
            class="text-sm font-medium text-brand-700 hover:underline"
          >
            Open
          </RouterLink>
          <button
            v-if="!item.is_system"
            type="button"
            class="text-sm font-medium text-rose-600 hover:underline"
            @click="onDelete(item)"
          >
            Delete
          </button>
        </div>
      </div>

      <div
        v-if="!store.loading && !store.savedViews.length"
        class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
      >
        No saved views yet. Save your current date and category filters to reuse them later.
      </div>
    </div>

    <div
      v-if="showCreate"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Save view</h3>
        <form class="mt-4 space-y-3" @submit.prevent="onCreate">
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
            <select v-model="form.category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option v-for="category in store.categories" :key="category.value" :value="category.value">
                {{ category.label }}
              </option>
            </select>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="showCreate = false">
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              Save view
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const showCreate = ref(false);
const form = reactive({
  name: '',
  category: 'operational',
});

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

async function onDelete(item) {
  if (!window.confirm(`Delete saved view "${item.name}"?`)) return;
  await store.deleteDashboard(item.uuid);
}

onMounted(async () => {
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await store.fetchSavedViews();
});
</script>
