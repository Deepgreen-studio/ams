<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.breaches.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.breaches.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Report incident
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div
      v-if="store.loading && !store.notificationStatistics"
      class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
    >
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
        <BreachNotificationSearchFilters
          :model-value="store.notificationFilters"
          @submit="onFilter"
          @reset="onReset"
        />
      </div>

      <BreachNotificationTable
        :notifications="store.notifications"
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
            :to="{ name: 'compliance.breaches.index' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            All incidents
          </RouterLink>
        </template>
      </BreachNotificationTable>

      <div v-if="store.notificationsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.notificationsMeta"
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
  EnvelopeIcon,
  ExclamationTriangleIcon,
  PaperAirplaneIcon,
  PlusIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import BreachNotificationSearchFilters from '@/modules/compliance/components/BreachNotificationSearchFilters.vue';
import BreachNotificationTable from '@/modules/compliance/components/BreachNotificationTable.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';
import Pagination from '@/modules/users/components/Pagination.vue';

const route = useRoute();
const store = useDataBreachStore();
const { can } = usePermissions();
const toast = useToast();

const cards = computed(() => {
  const stats = store.notificationStatistics || {};
  const sent = stats.sent ?? 0;
  const pending = stats.pending ?? 0;
  const failed = stats.failed ?? 0;
  const acknowledged = stats.acknowledged ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? store.notificationsMeta?.total ?? 0,
      hint: 'All drafted and sent notices',
      icon: EnvelopeIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Sent',
      value: sent,
      hint: sent ? 'Delivered to recipients' : 'No notices sent yet',
      icon: PaperAirplaneIcon,
      iconBg: sent ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: sent ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'Draft or queued for send' : 'Nothing waiting to send',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Failed',
      value: failed,
      hint: failed ? 'Needs a retry or new draft' : 'No delivery failures',
      icon: ExclamationTriangleIcon,
      iconBg: failed ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: failed ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Acknowledged',
      value: acknowledged,
      hint: acknowledged ? 'Recipients confirmed receipt' : 'No acknowledgements yet',
      icon: CheckCircleIcon,
      iconBg: acknowledged ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: acknowledged ? 'text-sky-500' : 'text-slate-500',
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
  ['status', 'notification_type', 'channel', 'search', 'company'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchNotifications(queryFilters).catch(() => {});
});

function onFilter(filters) {
  store.fetchNotifications(filters).catch(() => {});
}

function onReset() {
  store.resetNotificationFilters();
  store.fetchNotifications().catch(() => {});
}

function onPageChange(page) {
  store.fetchNotifications({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchNotifications({ per_page: perPage, page: 1 }).catch(() => {});
}
</script>
