<template>
  <div>
    <!-- <PageHeader title="Analytics Dashboards" description="Configure reusable analytics dashboards and widgets.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="showCreate = true"
        >
          Create dashboard
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
          Create dashboard
        </button>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="categoryOptions"
      show-search
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Name</th>
            <th class="px-4 py-3 font-medium">Category</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Widgets</th>
            <th class="px-4 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading dashboards…</td>
          </tr>
          <tr v-else-if="!store.dashboards.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No dashboards found.</td>
          </tr>
          <tr v-for="item in store.dashboards" :key="item.uuid" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.description || item.slug }}</p>
            </td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.category }}</td>
            <td class="px-4 py-3">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium capitalize text-slate-700">
                {{ item.status }}
              </span>
              <span v-if="item.is_system" class="ml-2 text-xs text-slate-400">system</span>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.widgets_count ?? 0 }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2">
                <RouterLink
                  :to="{ name: 'analytics.dashboards.designer', params: { uuid: item.uuid } }"
                  class="text-sm font-medium text-brand-700 hover:underline"
                >
                  Design
                </RouterLink>
                <RouterLink
                  :to="{ name: 'analytics.dashboards.show', params: { uuid: item.uuid } }"
                  class="text-sm font-medium text-slate-600 hover:underline"
                >
                  View
                </RouterLink>
                <button type="button" class="text-sm font-medium text-slate-600 hover:underline" @click="onDuplicate(item)">
                  Duplicate
                </button>
                <button
                  v-if="!item.is_system"
                  type="button"
                  class="text-sm font-medium text-rose-600 hover:underline"
                  @click="onDelete(item)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="showCreate"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Create dashboard</h3>
        <form class="mt-4 space-y-3" @submit.prevent="onCreate">
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
            <select v-model="form.category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option v-for="category in categoryOptions" :key="category.value" :value="category.value">
                {{ category.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
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
              Create
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const router = useRouter();
const showCreate = ref(false);

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
      ]
);

function onApply(next) {
  store.filters = { ...store.filters, ...next, kind: 'dashboard' };
  store.fetchDashboards();
}

async function onCreate() {
  const dashboard = await store.createDashboard({ ...form });
  showCreate.value = false;
  form.name = '';
  form.description = '';
  await store.fetchDashboards();
  if (dashboard?.uuid) {
    router.push({ name: 'analytics.dashboards.designer', params: { uuid: dashboard.uuid } });
  }
}

async function onDuplicate(item) {
  const copy = await store.duplicateDashboard(item.uuid);
  await store.fetchDashboards();
  if (copy?.uuid) {
    router.push({ name: 'analytics.dashboards.show', params: { uuid: copy.uuid } });
  }
}

async function onDelete(item) {
  if (!window.confirm(`Delete dashboard "${item.name}"?`)) return;
  await store.deleteDashboard(item.uuid);
}

onMounted(async () => {
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await store.fetchDashboards();
});
</script>
