<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.policies.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.policies.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New policy
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
        <PolicySearchFilters
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>

      <PolicyTable
        :policies="store.policies"
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
            :to="{ name: 'compliance.policies.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            New policy
          </RouterLink>
        </template>
      </PolicyTable>

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
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicySearchFilters from '@/modules/compliance/components/PolicySearchFilters.vue';
import PolicyTable from '@/modules/compliance/components/PolicyTable.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = usePolicyStore();
const { can } = usePermissions();
const toast = useToast();

const cards = computed(() => {
  const stats = store.statistics || {};
  const review = stats.review ?? 0;
  const published = stats.published ?? 0;
  const overdue = stats.review_overdue ?? 0;
  const draft = stats.draft ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? store.meta?.total ?? 0,
      hint: 'All governed documents',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Draft',
      value: draft,
      hint: draft ? 'Still being authored' : 'No drafts',
      icon: DocumentTextIcon,
      iconBg: 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
    {
      label: 'In review',
      value: review,
      hint: review ? 'Waiting for approval' : 'No reviews outstanding',
      icon: ClockIcon,
      iconBg: review ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: review ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Published',
      value: published,
      hint: published ? 'Live governed documents' : 'Nothing published yet',
      icon: CheckCircleIcon,
      iconBg: published ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: published ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Review overdue',
      value: overdue,
      hint: overdue ? 'Past scheduled review date' : 'All reviews on time',
      icon: ExclamationTriangleIcon,
      iconBg: overdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: overdue ? 'text-rose-500' : 'text-emerald-500',
    },
  ];
});

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
  ['status', 'policy_type', 'search', 'company'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchPolicies(queryFilters).catch(() => {});
});

function onFilter(filters) {
  store.fetchPolicies(filters).catch(() => {});
}

function onReset() {
  store.resetFilters();
  store.fetchPolicies().catch(() => {});
}

function onPageChange(page) {
  store.fetchPolicies({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchPolicies({ per_page: perPage, page: 1 }).catch(() => {});
}
</script>
