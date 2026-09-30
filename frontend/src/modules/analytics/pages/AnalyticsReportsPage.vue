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
        New Report
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
              placeholder="Search reports..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @input="onSearchInput"
              @search="onSearchInput"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="local.report_type"
              wrapper-class="min-w-[10.5rem]"
              :options="reportTypeOptions"
            />
            <SelectBox
              v-model="local.is_scheduled"
              wrapper-class="min-w-[9.5rem]"
              :options="scheduledOptions"
            />
            <button
              type="submit"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
            >
              Apply Filter
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="onReset"
            >
              Reset Filter
            </button>
          </div>
        </form>
      </div>

      <div class="overflow-x-auto px-3">
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
          <tbody v-if="store.loading && !store.reports.length">
            <tr v-for="n in 6" :key="n">
              <td colspan="12" class="px-5 py-3">
                <div class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
              </td>
            </tr>
          </tbody>
          <tbody v-else-if="!store.reports.length">
            <tr>
              <td colspan="12" class="p-0">
                <EmptyState
                  title="No reports found"
                  description="Try adjusting your filters or create a new tabular, chart, or scheduled report."
                >
                  <template #action>
                    <button
                      type="button"
                      class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
                      @click="onReset"
                    >
                      Reset Filter
                    </button>
                  </template>
                </EmptyState>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr
              v-for="item in store.reports"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                  >
                    {{ initials(item.name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ item.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ item.description || item.slug }}</p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 capitalize text-slate-600">{{ item.report_type || '—' }}</td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium capitalize"
                    :class="
                      item.status === 'active'
                        ? 'border-emerald-600 text-emerald-700'
                        : 'border-slate-300 text-slate-600'
                    "
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full"
                      :class="item.status === 'active' ? 'bg-emerald-600' : 'bg-slate-400'"
                    />
                    {{ item.status || 'draft' }}
                  </span>
                  <span
                    v-if="item.is_scheduled"
                    class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                  >
                    Scheduled
                  </span>
                  <span
                    v-if="item.is_saved"
                    class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"
                  >
                    Saved
                  </span>
                </div>
              </td>
              <td class="px-5 py-4 text-slate-600">{{ item.runs_count ?? 0 }}</td>
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
          v-if="openMenuId && activeReport"
          class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
          role="menu"
          :style="menuStyle"
          @click.stop
        >
          <RouterLink
            :to="{ name: 'analytics.reports.designer', params: { uuid: activeReport.uuid } }"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="closeMenu"
          >
            <PencilSquareIcon class="h-4 w-4 text-slate-400" />
            Design
          </RouterLink>
          <button
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
            role="menuitem"
            @click="onDelete(activeReport)"
          >
            <TrashIcon class="h-4 w-4 text-red-500" />
            Delete
          </button>
        </div>
      </Teleport>

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
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { EllipsisVerticalIcon, MagnifyingGlassIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
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
const openMenuId = ref(null);
const menuStyle = ref({});
let searchTimer = null;
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
  { value: '', label: 'Scheduled: All' },
  { value: '1', label: 'Scheduled' },
  { value: '0', label: 'Not scheduled' },
];

const fallbackTypes = [
  { value: 'tabular', label: 'Tabular' },
  { value: 'chart', label: 'Chart' },
  { value: 'grouped', label: 'Grouped' },
  { value: 'scheduled', label: 'Scheduled' },
];

const formTypeOptions = computed(() => (store.reportTypes.length ? store.reportTypes : fallbackTypes));

const reportTypeOptions = computed(() => [{ value: '', label: 'Type: All' }, ...formTypeOptions.value]);

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

const activeReport = computed(
  () => store.reports.find((item) => item.uuid === openMenuId.value) || null,
);

function initials(name) {
  return String(name || 'R')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 8 + 2 * 36;
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

function onSearchInput() {
  window.clearTimeout(searchTimer);
  const delay = String(local.search || '').trim() ? 300 : 0;
  searchTimer = window.setTimeout(() => onApply(), delay);
}

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
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);
  store.successMessage = null;
  store.error = null;
  await store.fetchReports();
});

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});
</script>
