<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.sla.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClockIcon class="h-4 w-4" />
        SLA dashboard
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.loading"
        @click="loadViolations()"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.loading }" />
        Refresh
      </button>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !store.violationSummary" class="mb-4 grid gap-4 sm:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-3">
      <div
        v-for="card in summaryCards"
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
      <div class="flex flex-col gap-4 border-b border-zinc-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Violation report</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Response and resolution breaches across tracked tickets.
          </p>
        </div>
        <SelectBox
          v-model="metric"
          wrapper-class="min-w-[12rem]"
          :options="metricOptions"
          @change="onFilterChange"
        />
      </div>

      <div v-if="store.loading && !store.violations.length" class="space-y-3 px-6 py-5 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.violations.length"
        title="No violations found"
        description="Tickets that miss response or resolution targets will appear here."
      >
        <template #action>
          <RouterLink
            :to="{ name: 'support.sla.dashboard' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            View SLA timers
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Ticket</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">SLA</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Response breach
              </th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Resolution breach
              </th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Assignee</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="ticket in store.violations"
              :key="ticket.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <div class="flex flex-wrap items-center gap-2">
                  <RouterLink
                    :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                    class="font-medium text-slate-900 hover:text-brand-700"
                  >
                    {{ ticket.ticket_number }}
                  </RouterLink>
                  <span
                    v-if="ticket.source === 'sms'"
                    class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-sky-100"
                  >
                    SMS
                  </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">{{ ticket.subject }}</p>
              </td>
              <td class="px-5 py-4">
                <SlaStatusBadge :status="ticket.sla_status" :label="ticket.sla_status_label" />
              </td>
              <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
                {{ formatDate(ticket.response_breached_at) }}
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
                {{ formatDate(ticket.resolution_breached_at) }}
              </td>
              <td class="px-5 py-4 text-slate-600">
                {{ ticket.assignee?.full_name || 'Unassigned' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.violationMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.violationMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowPathIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SlaStatusBadge from '@/modules/support/components/SlaStatusBadge.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();
const toast = useToast();
const metric = ref('');
const perPage = ref(10);

const metricOptions = [
  { value: '', label: 'All metrics' },
  { value: 'response', label: 'Response' },
  { value: 'resolution', label: 'Resolution' },
];

const summaryCards = computed(() => {
  const total = store.violationSummary?.total ?? 0;
  const response = store.violationSummary?.response ?? 0;
  const resolution = store.violationSummary?.resolution ?? 0;

  return [
    {
      label: 'Total',
      value: total,
      hint: 'Tickets with any SLA breach',
      icon: ShieldExclamationIcon,
      iconBg: total ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: total ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Response',
      value: response,
      hint: response ? 'First response missed' : 'No response breaches',
      icon: ChatBubbleLeftRightIcon,
      iconBg: response ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: response ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Resolution',
      value: resolution,
      hint: resolution ? 'Resolution target missed' : 'No resolution breaches',
      icon: ExclamationTriangleIcon,
      iconBg: resolution ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: resolution ? 'text-rose-500' : 'text-slate-500',
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
  store.error = null;
  loadViolations().catch(() => {});
});

function reportParams(page = store.violationMeta?.current_page || 1) {
  return {
    page,
    per_page: perPage.value,
    metric: metric.value || undefined,
  };
}

async function loadViolations(page = 1) {
  await store.fetchViolations(reportParams(page));
}

function onFilterChange() {
  loadViolations(1).catch(() => {});
}

function onPageChange(page) {
  loadViolations(page).catch(() => {});
}

function onPerPage(value) {
  perPage.value = value;
  loadViolations(1).catch(() => {});
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
