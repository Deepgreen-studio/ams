<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="showCreate = true"
      >
        <PlusIcon class="h-4 w-4" />
        New report
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <form class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between" @submit.prevent="onApply">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
            <div class="relative">
              <MagnifyingGlassIcon
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              />
              <input
                v-model="local.search"
                type="search"
                placeholder="Search reports…"
                class="input pl-10"
              />
            </div>
          </div>
          <div class="flex flex-wrap items-end gap-2">
            <div class="min-w-[10.5rem]">
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
              <SelectBox v-model="local.report_type" :options="reportTypeOptions" />
            </div>
            <div class="min-w-[8.5rem]">
              <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Scheduled</label>
              <SelectBox v-model="local.is_scheduled" :options="scheduledOptions" />
            </div>
            <button
              type="submit"
              class="inline-flex h-12 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
            >
              Apply
            </button>
            <button
              type="button"
              class="inline-flex h-12 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="onReset"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      <div v-if="store.loading && !store.reports.length" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.reports.length"
        title="No reports found"
        description="Try adjusting your filters or create a new tabular, chart, or scheduled report."
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
            New report
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Name</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Type</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Runs</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.reports"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.description || item.slug }}</p>
              </td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ item.report_type }}</td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                  :class="
                    item.status === 'active'
                      ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                      : 'bg-slate-50 text-slate-700 ring-slate-500/20'
                  "
                >
                  {{ item.status }}
                </span>
                <span v-if="item.is_scheduled" class="ml-2 text-xs text-amber-600">scheduled</span>
                <span v-if="item.is_saved" class="ml-2 text-xs text-emerald-600">saved</span>
              </td>
              <td class="px-5 py-4 text-slate-600">{{ item.runs_count ?? 0 }}</td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-1">
                  <RouterLink
                    :to="{ name: 'analytics.reports.designer', params: { uuid: item.uuid } }"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  >
                    Design
                  </RouterLink>
                  <button
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                    @click="pendingDelete = item"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.reportsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.reportsMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <div
      v-if="showCreate"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-lg overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h3 class="text-base font-semibold text-slate-900">Create report</h3>
          <p class="mt-0.5 text-xs text-slate-500">Start a tabular, chart, grouped, or scheduled report.</p>
        </div>
        <form class="space-y-4 px-6 py-5" @submit.prevent="onCreate">
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
            <input v-model="form.name" type="text" required class="input" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
            <SelectBox v-model="form.report_type" :options="formTypeOptions" />
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
              :disabled="store.saving || !form.name"
            >
              Create
            </button>
          </div>
        </form>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete report"
      :message="`Delete report “${pendingDelete?.name}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
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
const local = reactive({ search: '', report_type: '', is_scheduled: '' });
const form = reactive({
  name: '',
  description: '',
  report_type: 'tabular',
  status: 'draft',
  is_saved: true,
  visibility: 'personal',
});

const scheduledOptions = [
  { value: '', label: 'All' },
  { value: '1', label: 'Yes' },
  { value: '0', label: 'No' },
];

const fallbackTypes = [
  { value: 'tabular', label: 'Tabular' },
  { value: 'chart', label: 'Chart' },
  { value: 'grouped', label: 'Grouped' },
  { value: 'scheduled', label: 'Scheduled' },
];

const formTypeOptions = computed(() => (store.reportTypes.length ? store.reportTypes : fallbackTypes));

const reportTypeOptions = computed(() => [{ value: '', label: 'All types' }, ...formTypeOptions.value]);

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

async function onApply() {
  await store.fetchReports({ ...local, page: 1, per_page: store.filters.per_page });
}

function onReset() {
  local.search = '';
  local.report_type = '';
  local.is_scheduled = '';
  store.fetchReports({ search: '', report_type: '', is_scheduled: '', page: 1 }).catch(() => {});
}

function onPageChange(page) {
  store.fetchReports({ ...local, page, per_page: store.filters.per_page }).catch(() => {});
}

function onPerPage(perPage) {
  store.filters = { ...store.filters, per_page: perPage };
  store.fetchReports({ ...local, per_page: perPage, page: 1 }).catch(() => {});
}

async function onCreate() {
  const report = await store.createReport({ ...form });
  showCreate.value = false;
  form.name = '';
  form.description = '';
  if (report?.uuid) {
    await router.push({ name: 'analytics.reports.designer', params: { uuid: report.uuid } });
  }
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  try {
    await store.deleteReport(pendingDelete.value.uuid);
    pendingDelete.value = null;
  } catch {
    pendingDelete.value = null;
  }
}

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  await store.fetchReports();
});
</script>
