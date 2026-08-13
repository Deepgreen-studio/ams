<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.dpia.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.dpia.wizard' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New DPIA
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !store.dpiaStatistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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
        <DpiaSearchFilters
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>

      <DpiaTable
        :assessments="store.assessments"
        :loading="store.loading"
        :framed="false"
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
            :to="{ name: 'compliance.dpia.wizard' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            New DPIA
          </RouterLink>
        </template>
      </DpiaTable>

      <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaSearchFilters from '@/modules/compliance/components/DpiaSearchFilters.vue';
import DpiaTable from '@/modules/compliance/components/DpiaTable.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = useDpiaStore();
const { can } = usePermissions();
const toast = useToast();

const cards = computed(() => {
  const stats = store.dpiaStatistics || {};
  const active = stats.active ?? 0;
  const pending = stats.pending_review ?? 0;
  const overdue = stats.review_overdue ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? store.meta?.total ?? 0,
      hint: 'All recorded assessments',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Active',
      value: active,
      hint: active ? 'Draft, in progress, or in review' : 'No active assessments',
      icon: ClipboardDocumentCheckIcon,
      iconBg: active ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: active ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Pending review',
      value: pending,
      hint: pending ? 'Waiting for approval' : 'No reviews outstanding',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Review overdue',
      value: overdue,
      hint: overdue ? 'Past scheduled review date' : 'All reviews on time',
      icon: ExclamationTriangleIcon,
      iconBg: overdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: overdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Approved',
      value: stats.approved ?? 0,
      hint: 'Assessments signed off',
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
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
  ['status', 'template_code', 'overall_risk_level', 'search', 'review_overdue'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchAssessments(queryFilters).catch(() => {});
});

function onFilter(filters) {
  store.fetchAssessments(filters).catch(() => {});
}

function onReset() {
  store.resetFilters();
  store.fetchAssessments().catch(() => {});
}

function onPageChange(page) {
  store.fetchAssessments({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchAssessments({ per_page: perPage, page: 1 }).catch(() => {});
}
</script>
