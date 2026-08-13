<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.consents.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.consents.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Record consent
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
        <ConsentSearchFilters
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>

      <ConsentTable
        :consents="store.consents"
        :loading="store.loading"
        :framed="false"
        @withdraw="openWithdraw"
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
            :to="{ name: 'compliance.consents.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Record consent
          </RouterLink>
        </template>
      </ConsentTable>

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
      :open="Boolean(pendingWithdraw)"
      title="Withdraw consent"
      :message="`Withdraw consent for ${pendingWithdraw?.subject_name || pendingWithdraw?.subject_email || 'this subject'}? The record will be marked withdrawn.`"
      confirm-label="Withdraw"
      :loading="store.saving"
      @cancel="pendingWithdraw = null"
      @confirm="confirmWithdraw"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  NoSymbolIcon,
  PlusIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentSearchFilters from '@/modules/compliance/components/ConsentSearchFilters.vue';
import ConsentTable from '@/modules/compliance/components/ConsentTable.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = useConsentStore();
const { can } = usePermissions();
const toast = useToast();
const pendingWithdraw = ref(null);

const cards = computed(() => {
  const stats = store.statistics || {};
  const granted = stats.granted ?? 0;
  const pending = stats.pending ?? 0;
  const withdrawn = stats.withdrawn ?? 0;
  const expired = stats.expired ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? store.meta?.total ?? 0,
      hint: 'All captured consent records',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Granted',
      value: granted,
      hint: granted ? 'Subjects currently opted in' : 'No granted consents',
      icon: CheckCircleIcon,
      iconBg: granted ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: granted ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'Awaiting confirmation' : 'Nothing pending',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Withdrawn',
      value: withdrawn,
      hint: withdrawn ? 'Opt-outs on record' : 'No withdrawals',
      icon: NoSymbolIcon,
      iconBg: withdrawn ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: withdrawn ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Expired',
      value: expired,
      hint: expired ? 'Needs recapture' : 'No expired records',
      icon: ExclamationTriangleIcon,
      iconBg: expired ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: expired ? 'text-rose-500' : 'text-emerald-500',
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
  ['status', 'channel', 'source', 'search', 'granted'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchConsents(queryFilters).catch(() => {});
});

function onFilter(filters) {
  store.fetchConsents(filters).catch(() => {});
}

function onReset() {
  store.resetFilters();
  store.fetchConsents().catch(() => {});
}

function onPageChange(page) {
  store.fetchConsents({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchConsents({ per_page: perPage, page: 1 }).catch(() => {});
}

function openWithdraw(item) {
  pendingWithdraw.value = item;
}

async function confirmWithdraw() {
  if (!pendingWithdraw.value) {
    return;
  }

  try {
    await store.withdrawConsent(pendingWithdraw.value.uuid, {
      notes: 'Withdrawn from consent list',
    });
    toast.success(store.successMessage || 'Consent withdrawn successfully.');
    store.successMessage = null;
    pendingWithdraw.value = null;
    await store.fetchConsents();
  } catch {
    pendingWithdraw.value = null;
  }
}
</script>
