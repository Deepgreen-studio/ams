<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.cases.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create case
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !store.statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in cards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <CaseSearchFilters
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>

      <CaseTable
        :cases="store.cases"
        :loading="store.loading"
        :framed="false"
        @delete="openDelete"
      >
        <template #empty-action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset filters
          </button>
          <RouterLink
            v-if="can('compliance.create')"
            :to="{ name: 'compliance.cases.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create case
          </RouterLink>
        </template>
      </CaseTable>

      <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete compliance case"
      :message="`Soft delete ${pendingDelete?.title || 'this case'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ExclamationTriangleIcon,
  FolderOpenIcon,
  InboxIcon,
  PlusIcon,
  Squares2X2Icon,
  UserMinusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import CaseSearchFilters from '@/modules/compliance/components/CaseSearchFilters.vue';
import CaseTable from '@/modules/compliance/components/CaseTable.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = useComplianceStore();
const { can } = usePermissions();
const toast = useToast();
const pendingDelete = ref(null);

const cards = computed(() => {
  const stats = store.statistics || {};
  const active = stats.active ?? 0;
  const overdue = stats.overdue ?? 0;
  const critical = stats.critical ?? 0;
  const unassigned = stats.unassigned ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? store.meta?.total ?? 0,
      hint: 'All recorded cases',
      icon: FolderOpenIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Active',
      value: active,
      hint: active ? 'Open, in progress, or pending' : 'No active cases',
      icon: InboxIcon,
      iconBg: active ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: active ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Overdue',
      value: overdue,
      hint: overdue ? 'Past due date' : 'All active cases on time',
      icon: ExclamationTriangleIcon,
      iconBg: overdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: overdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Critical',
      value: critical,
      hint: critical ? 'Needs immediate attention' : 'No critical cases',
      icon: ExclamationTriangleIcon,
      iconBg: critical ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: critical ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Unassigned',
      value: unassigned,
      hint: unassigned ? 'Needs an owner' : 'All active cases assigned',
      icon: UserMinusIcon,
      iconBg: unassigned ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: unassigned ? 'text-amber-500' : 'text-slate-500',
    },
  ];
});

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

  const queryFilters = {};
  ['status', 'priority', 'case_type', 'company', 'search', 'overdue'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchCases(queryFilters).catch(() => {});
});

function onFilter(filters) {
  store.fetchCases(filters).catch(() => {});
}

function onReset() {
  store.resetFilters();
  store.fetchCases().catch(() => {});
}

function onPageChange(page) {
  store.fetchCases({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchCases({ per_page: perPage, page: 1 }).catch(() => {});
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) {
    return;
  }

  try {
    await store.deleteCase(pendingDelete.value.uuid);
    toast.success(store.successMessage || 'Compliance case deleted successfully.');
    store.successMessage = null;
    pendingDelete.value = null;
    await store.fetchCases();
  } catch {
    pendingDelete.value = null;
  }
}
</script>
